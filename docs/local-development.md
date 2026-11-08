# Local development

Follow the [root README](../README.md) for Docker setup and Laravel tests. Laravel serves the workspace and API from one process. The local Compose setup is for development; do not expose it publicly. Database data persists in the `mysql_data` volume; `docker compose down -v` removes it.
