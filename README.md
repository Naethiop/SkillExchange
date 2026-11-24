# SkillExchange

Smart learning platform for university students.

## Deploy on Railway

1. Create a Railway project from this repository. Railway will build the root `Dockerfile`.
2. Add a Railway MySQL service to the project.
3. In the web service's **Variables**, add references to the MySQL service variables:
   - `MYSQLHOST` → `${{MySQL.MYSQLHOST}}`
   - `MYSQLPORT` → `${{MySQL.MYSQLPORT}}`
   - `MYSQLDATABASE` → `${{MySQL.MYSQLDATABASE}}`
   - `MYSQLUSER` → `${{MySQL.MYSQLUSER}}`
   - `MYSQLPASSWORD` → `${{MySQL.MYSQLPASSWORD}}`
   Replace `MySQL` with the exact name of your database service if it differs.
4. Import `Backend/skill_exchange_schema.sql` into the Railway database using a MySQL client. This file creates the tables without including personal/sample account records.
5. Generate a public domain for the web service in Railway's **Networking** settings. The container listens on Railway's `PORT` automatically.

For local development, the app still defaults to MySQL at `localhost` with the database `skill_exchange_db`. You can also set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD`; Railway's `MYSQL*` variables take precedence.
