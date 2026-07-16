package source

// Disk-image (bare-metal) source: reads one or more block devices into raw
// image files in a spool directory, which kopia then snapshots. Content-
// addressed dedup means only the changed blocks cost storage across runs, so
// nightly full-device images stay cheap. Requires the agent to run as root.
//
// Restore lands the .img file(s) back on disk; writing an image back onto a
// device (dd if=name.img of=/dev/sdX) is a manual, deliberate step, kept out of
// the automated restore path on purpose.

import (
	"context"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"strings"
)

// DiskImage images a set of block devices into a spool directory.
type DiskImage struct {
	devices []string
}

// NewDiskImage builds a disk-image source for the given devices (e.g. /dev/sda).
func NewDiskImage(devices []string) *DiskImage {
	return &DiskImage{devices: devices}
}

func (d *DiskImage) Kind() string { return "diskimage" }

func (d *DiskImage) Materialize(ctx context.Context) (*Materialized, error) {
	var devs []string
	for _, dev := range d.devices {
		if strings.TrimSpace(dev) != "" {
			devs = append(devs, strings.TrimSpace(dev))
		}
	}
	if len(devs) == 0 {
		return nil, fmt.Errorf("disk image: no devices configured")
	}
	if _, err := exec.LookPath("dd"); err != nil {
		return nil, fmt.Errorf("disk image: `dd` not found on the host")
	}

	spool, err := os.MkdirTemp("", "backup-image-*")
	if err != nil {
		return nil, err
	}
	cleanup := func() error { return os.RemoveAll(spool) }

	for _, dev := range devs {
		if fi, err := os.Stat(dev); err != nil {
			cleanup()
			return nil, fmt.Errorf("disk image: %s: %w", dev, err)
		} else if fi.Mode()&os.ModeDevice == 0 && !fi.Mode().IsRegular() {
			cleanup()
			return nil, fmt.Errorf("disk image: %s is not a block device", dev)
		}
		out := filepath.Join(spool, imageName(dev))
		// conv=sparse keeps zero runs sparse so empty space is not written out;
		// kopia further dedups identical blocks between runs.
		cmd := exec.CommandContext(ctx, "dd",
			"if="+dev, "of="+out, "bs=4M", "conv=sparse")
		if b, err := cmd.CombinedOutput(); err != nil {
			cleanup()
			return nil, fmt.Errorf("disk image: dd %s: %v: %s", dev, err, strings.TrimSpace(string(b)))
		}
	}

	return &Materialized{Path: spool, Cleanup: cleanup}, nil
}

// imageName turns /dev/sda into sda.img (safe, flat filename).
func imageName(dev string) string {
	base := filepath.Base(strings.TrimRight(dev, "/"))
	if base == "" || base == "." || base == "/" {
		base = strings.ReplaceAll(strings.Trim(dev, "/"), "/", "-")
	}
	return base + ".img"
}
