package inventory

import (
	"errors"
	"net"
	"strings"
)

type Input struct {
	Name         string `json:"name"`
	BuildingID   int64  `json:"building_id"`
	LocationID   int64  `json:"location_id"`
	AssetID      string `json:"asset_id"`
	Manufacturer string `json:"manufacturer"`
	Model        string `json:"model"`
	SerialNumber string `json:"serial_number"`
	ManagementIP string `json:"management_ip"`
	Category     string `json:"category"`
	Notes        string `json:"notes"`
}

func (in *Input) statement(resource string) (string, []any, error) {
	in.Name = strings.TrimSpace(in.Name)
	if in.Name == "" || len(in.Name) > 120 {
		return "", nil, errors.New("name required, maximum 120 bytes")
	}
	var query string
	var args []any
	switch resource {
	case "buildings":
		query = "INSERT INTO buildings(name) VALUES(?)"
		args = []any{in.Name}
	case "locations":
		if in.BuildingID < 1 {
			return "", nil, errors.New("building required")
		}
		query = "INSERT INTO locations(name,building_id) VALUES(?,?)"
		args = []any{in.Name, in.BuildingID}
	case "devices":
		if strings.TrimSpace(in.AssetID) == "" || len(in.AssetID) > 80 || in.LocationID < 1 || in.Category == "" || len(in.Category) > 50 || len(in.Manufacturer) > 80 || len(in.Model) > 80 || len(in.SerialNumber) > 120 || (in.ManagementIP != "" && net.ParseIP(in.ManagementIP) == nil) {
			return "", nil, errors.New("valid asset ID, location, category and management IP required")
		}
		query = "INSERT INTO devices(name,asset_id,manufacturer,model,serial_number,management_ip,category,location_id,notes) VALUES(?,?,?,?,?,?,?,?,?)"
		args = []any{in.Name, in.AssetID, in.Manufacturer, in.Model, in.SerialNumber, in.ManagementIP, in.Category, in.LocationID, in.Notes}
	}

	if query == "" {
		return "", nil, errors.New("unsupported resource")
	}
	return query, args, nil
}
