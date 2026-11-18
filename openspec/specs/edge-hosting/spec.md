# edge-hosting Specification

## Purpose

SIPRAS runs as a hosted web app on Wasmer Edge: a single deploy publishes the whole Laravel application, uploaded complaint images survive redeploys, the app talks to the platform-attached MySQL database, and production secrets never enter the repository while the local XAMPP workflow keeps working unchanged.

## Requirements

### Requirement: One-command deploy publishes the app

The project SHALL include deployment configuration so that running the Wasmer deploy from the project root publishes the complete Laravel application to an HTTPS Wasmer Edge URL without manual server steps.

#### Scenario: Fresh deploy serves the app

- **WHEN** the deploy command finishes for a first deployment
- **THEN** the application URL serves the app: the health endpoint responds 200 and `/` behaves as in local development (redirect to the login page for guests)

#### Scenario: Preflight checks pass before deploy

- **WHEN** validation of the deployment configuration runs
- **THEN** it reports no errors for the deploy descriptor

### Requirement: Uploaded images are persistent on the Edge

Files a user uploads (complaint evidence, resolution proof) SHALL be stored on a persistent platform volume served directly under the app's `/storage` URL path, and SHALL remain present and reachable after app redeploys and restarts.

#### Scenario: Evidence survives a redeploy

- **WHEN** a student uploads an evidence image and the app is then redeployed
- **THEN** the image is still available at the same URL and the complaint detail page renders it

#### Scenario: No symlink in the serving path

- **WHEN** the public uploads directory is examined on the Edge
- **THEN** uploaded files a browser requests are served directly from the mounted persistent directory

### Requirement: Database is provided by the platform MySQL

The deployed app SHALL use the Wasmer-attached MySQL database through platform-provided database environment variables, and the migration and seed data for a fresh deployment SHALL be initialized exactly once and idempotently, meaning a repeated initialization attempt must not duplicate seed rows or error.

#### Scenario: Fresh deployment becomes fully usable

- **WHEN** a new deployment is initialized with an empty platform database
- **THEN** the schema and seed data exist in the platform MySQL and an administrator can sign in and see the seeded complaints

#### Scenario: Initialization is idempotent

- **WHEN** the initialization routine runs a second time against the same database
- **THEN** no duplicate seed rows are created and the app keeps serving the existing data unchanged

#### Scenario: Local dialect compatibility

- **WHEN** the application schema and queries run on the attached MySQL
- **THEN** all shipped features work with no dialect-specific failures (the schema already targets MariaDB locally)

### Requirement: Evidence upload limits are honored on the Edge

The hosted PHP configuration SHALL allow uploads strictly larger than the application's 2 MB evidence cap, so the application's own Indonesian validation messages remain the effective limit for users.

#### Scenario: Cap-sized upload succeeds server side

- **WHEN** a student uploads a valid image up to the app's cap on the Edge
- **THEN** the platform storage layer accepts the file and no platform-level size error appears

### Requirement: Production secrets never live in the repository

The repository SHALL contain no production secret values (application key, database passwords); the deployment SHALL receive them exclusively from platform environment/secret configuration, and the deployed app SHALL boot cleanly with those secrets.

#### Scenario: Repository scan finds no committed secrets

- **WHEN** the deployment configuration files are inspected in the repository
- **THEN** no production application key or database credentials appear in them

### Requirement: Local development workflow remains unchanged

Hosting configuration SHALL NOT change the local developer workflow: the XAMPP MariaDB dev database, `composer serve`, and the in-memory-SQLite test runs SHALL keep working exactly as before the hosting change.

#### Scenario: Test suite still passes locally

- **WHEN** the full feature test suite runs on the local machine
- **THEN** tests pass on the same storage layout, database dialect, and routes as before the change
