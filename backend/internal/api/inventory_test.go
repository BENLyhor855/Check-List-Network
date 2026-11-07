package api

import "testing"

func TestParseOffset(t *testing.T) {
	for _, v := range []string{"-1", "1000001", "1junk", "1.5", "no"} {
		if _, err := parseOffset(v); err == nil {
			t.Errorf("accepted invalid offset %q", v)
		}
	}
	for _, v := range []string{"", "0", "100", "1000000"} {
		if _, err := parseOffset(v); err != nil {
			t.Errorf("rejected valid offset %q", v)
		}
	}
}
