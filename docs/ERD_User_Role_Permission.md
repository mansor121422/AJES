# ERD: User, Role, and Permission

## Entity-Relationship Diagram (Mermaid)

Copy the code below into [Mermaid Live Editor](https://mermaid.live) to view or export as image.

```mermaid
erDiagram
    USERS ||--o{ ROLES : "has"
    ROLES ||--o{ ROLE_PERMISSIONS : "has"
    PERMISSIONS ||--o{ ROLE_PERMISSIONS : "has"

    USERS {
        int id PK
        string username
        string email
        string name
        string password_hash
        int role_id FK
        datetime created_at
    }

    ROLES {
        int id PK
        string name
        string description
    }

    PERMISSIONS {
        int id PK
        string name
        string resource
    }

    ROLE_PERMISSIONS {
        int role_id FK
        int permission_id FK
    }
```

## Simplified (AJES-style: role as column)

If roles are stored as a column on Users (like in AJES):

```mermaid
erDiagram
    USERS }o--|| ROLES : "belongs to"

    USERS {
        int id PK
        string username
        string email
        string name
        string password_hash
        string role
        int section_id FK
        tinyint is_active
        datetime created_at
    }

    ROLES {
        string name
    }
```

## Relationship summary

| From   | To               | Relationship | Description                    |
|--------|------------------|--------------|--------------------------------|
| Users  | Roles           | Many-to-One  | Many users have one role       |
| Roles  | Permissions     | Many-to-Many | Roles have many permissions    |
|        | (via role_permissions) |            | Permissions belong to many roles |
