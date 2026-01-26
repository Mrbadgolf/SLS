# Operations Dashboard

A self-hosted operations dashboard for Docker-based streaming stacks. Provides container controls, live system stats, and streaming activity visibility in a single webpage.

## Features

- **Container Management**: Start, stop, restart containers directly from the dashboard
- **Live System Stats**: CPU, RAM, disk, and network monitoring
- **Container Stats**: Per-container CPU and memory usage
- **Streaming Activity**: Track active viewers by parsing Traefik access logs
- **Backup/Restore**: Integration with restic for one-click backups
- **Quick Links**: Direct access to OliveTin, Netdata, and Portainer
- **Secure Access**: TLS + Basic Auth via Traefik file provider

## Architecture

This dashboard follows the philosophy of specialized tools:

| Component | Role |
|-----------|------|
| **Ops Dashboard** | Unified view & container controls |
| **OliveTin** | Actions & operational scripts |
| **Netdata** | Deep system monitoring & alerts |
| **Portainer** | Advanced container management |
| **Traefik** | Routing & TLS (file provider) |
| **restic** | Backup & recovery |

## Requirements

- Docker & Docker Compose
- Traefik (with file provider enabled)
- Docker networks: `stremio`, `internal`
- (Optional) restic for backup functionality
- (Optional) Traefik access logs for streaming activity

## Installation

### 1. Clone and Build

```bash
cd /opt
git clone <repository> ops-dashboard
cd ops-dashboard
docker compose build
```

### 2. Configure Environment

Create a `.env` file:

```bash
# External service URLs
OLIVETIN_URL=https://olivetin.geaux-tv.com
NETDATA_URL=https://netdata.geaux-tv.com
PORTAINER_URL=https://portainer.geaux-tv.com

# Traefik log location
TRAEFIK_LOG_DIR=/var/log/traefik

# Compose files location
COMPOSE_DIR=/opt/stremio-stack

# Backup configuration
BACKUP_DIR=/opt/backups
RESTIC_PASSWORD=your-secure-password
```

### 3. Setup Traefik Routing

Copy the Traefik file provider configuration:

```bash
cp config/traefik-ops-dashboard.yml /opt/stremio-stack/traefik/dynamic/
```

Edit the domain in the configuration file:
- Change `ops.geaux-tv.com` to your domain
- Update the Basic Auth credentials if needed

### 4. Create Docker Networks (if not exists)

```bash
docker network create stremio
docker network create internal
```

### 5. Start the Dashboard

```bash
docker compose up -d
```

### 6. Access the Dashboard

Visit: `https://ops.your-domain.com`

Login with the configured Basic Auth credentials.

## Configuration

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `OLIVETIN_URL` | `https://olivetin.geaux-tv.com` | OliveTin dashboard URL |
| `NETDATA_URL` | `https://netdata.geaux-tv.com` | Netdata dashboard URL |
| `PORTAINER_URL` | `https://portainer.geaux-tv.com` | Portainer URL |
| `DOCKER_SOCKET` | `/var/run/docker.sock` | Docker socket path |
| `TRAEFIK_ACCESS_LOG` | `/var/log/traefik/access.log` | Traefik access log |
| `STREAMING_PATTERNS` | `/stream,/play,/video,...` | URL patterns for streaming detection |
| `ACTIVE_WINDOW_MINUTES` | `5` | Time window for active stream counting |
| `RESTIC_REPOSITORY` | `/opt/backups/restic` | Restic repository path |
| `RESTIC_PASSWORD` | - | Restic repository password |
| `COMPOSE_DIR` | `/opt/stremio-stack` | Docker Compose files location |
| `MONITOR_DISKS` | `/` | Disk paths to monitor (comma-separated) |

### Traefik File Provider

The dashboard uses Traefik's file provider for routing. Key configuration:

```yaml
http:
  routers:
    ops-dashboard-https:
      rule: "Host(`ops.geaux-tv.com`)"
      entryPoints:
        - websecure
      middlewares:
        - ops-dashboard-auth
      service: ops-dashboard-service
      tls:
        certResolver: le

  services:
    ops-dashboard-service:
      loadBalancer:
        servers:
          - url: "http://ops-dashboard:8080"
```

### Basic Auth

Generate a new password hash:

```bash
htpasswd -nbB username password
```

Update the middleware in `traefik-ops-dashboard.yml`.

## OliveTin Integration

Copy the example OliveTin actions:

```bash
cp config/olivetin-actions.yaml /etc/olivetin/
```

These provide buttons for:
- Backup/Restore operations
- Stack controls (start/stop/restart)
- Container management
- System information

## Scripts

The `scripts/` directory contains operational scripts:

| Script | Description |
|--------|-------------|
| `backup.sh` | Create restic backup for a stack |
| `restore.sh` | Restore from a restic snapshot |
| `update-stack.sh` | Safe update (backup → pull → recreate) |
| `streaming-count.sh` | Count active streams from Traefik logs |

## API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/` | GET | Main dashboard page |
| `/api/health` | GET | Health check |
| `/api/containers` | GET | List all containers |
| `/api/container/<id>/action` | POST | Container action (start/stop/restart) |
| `/api/container/<id>/stats` | GET | Container resource stats |
| `/api/system/stats` | GET | System-wide stats |
| `/api/streaming/active` | GET | Active streaming count |
| `/api/backup/<stack>` | POST | Create backup |
| `/api/restore/<stack>` | POST | Restore from backup |
| `/api/backup/<stack>/snapshots` | GET | List snapshots |

## Security

- No ports exposed directly (Traefik only)
- Basic Auth required for all routes
- HTTPS enforced with automatic redirect
- Security headers applied
- Docker socket mounted read-only
- Container runs with resource limits

## Troubleshooting

### Dashboard not accessible

1. Check container is running: `docker ps | grep ops-dashboard`
2. Check container logs: `docker logs ops-dashboard`
3. Verify Traefik config is loaded: Check Traefik dashboard
4. Ensure networks exist: `docker network ls`

### No streaming activity shown

1. Verify Traefik access log path
2. Check log format (JSON or Common Log Format)
3. Adjust `STREAMING_PATTERNS` to match your URLs

### Backup/Restore not working

1. Initialize restic repo: `restic init`
2. Check `RESTIC_PASSWORD` is set
3. Verify backup directory permissions

## License

MIT License
