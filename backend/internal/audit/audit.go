// Package audit records successful mutations inside the caller's transaction.
package audit

import (
	"context"
	"database/sql"
)

func Record(ctx context.Context, tx *sql.Tx, actorID int64, action, target string) error {
	_, err := tx.ExecContext(ctx, "INSERT INTO audit_logs(user_id,action,target,result) VALUES(?,?,?,'success')", actorID, action, target)
	return err
}
