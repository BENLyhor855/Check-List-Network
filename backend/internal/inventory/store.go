// Package inventory owns inventory validation and database operations.
package inventory

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"racnoc/internal/audit"
)

const PageSize = 100

type Store struct{ DB *sql.DB }
type ValidationError struct{ Message string }

func (e *ValidationError) Error() string { return e.Message }

var ErrConflict = errors.New("record conflicts or referenced location/building does not exist")
var queries = map[string]string{"buildings": "SELECT id,name FROM buildings ORDER BY id LIMIT 100 OFFSET ?", "locations": "SELECT id,building_id,name FROM locations ORDER BY id LIMIT 100 OFFSET ?", "devices": "SELECT d.id,d.asset_id,d.name,d.manufacturer,d.model,d.management_ip,d.category,d.status,l.name location,b.name building FROM devices d JOIN locations l ON l.id=d.location_id JOIN buildings b ON b.id=l.building_id ORDER BY d.id LIMIT 100 OFFSET ?", "audit": "SELECT id,user_id,action,target,result,created_at FROM audit_logs ORDER BY id DESC LIMIT 100 OFFSET ?"}

func (s Store) List(ctx context.Context, resource string, offset int) ([]map[string]any, error) {
	query, ok := queries[resource]
	if !ok {
		return nil, &ValidationError{"unsupported resource"}
	}
	rows, err := s.DB.QueryContext(ctx, query, offset)
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	cols, err := rows.Columns()
	if err != nil {
		return nil, err
	}
	items := []map[string]any{}
	for rows.Next() {
		values := make([]any, len(cols))
		ptr := make([]any, len(cols))
		for i := range values {
			ptr[i] = &values[i]
		}
		if err = rows.Scan(ptr...); err != nil {
			return nil, err
		}
		item := map[string]any{}
		for i, k := range cols {
			if b, ok := values[i].([]byte); ok {
				item[k] = string(b)
			} else {
				item[k] = values[i]
			}
		}
		items = append(items, item)
	}
	return items, rows.Err()
}
func (s Store) Create(ctx context.Context, actorID int64, resource string, in Input) (int64, error) {
	query, args, err := in.statement(resource)
	if err != nil {
		return 0, &ValidationError{err.Error()}
	}
	tx, err := s.DB.BeginTx(ctx, nil)
	if err != nil {
		return 0, err
	}
	defer tx.Rollback()
	result, err := tx.ExecContext(ctx, query, args...)
	if err != nil {
		return 0, fmt.Errorf("%w: %v", ErrConflict, err)
	}
	id, err := result.LastInsertId()
	if err != nil {
		return 0, err
	}
	if err = audit.Record(ctx, tx, actorID, resource+".create", fmt.Sprint(id)); err != nil {
		return 0, err
	}
	if err = tx.Commit(); err != nil {
		return 0, err
	}
	return id, nil
}
