# ALERTO APP — Login & Registration Workflows

## 1. Landing Gateway Workflow

The main gateway is served at `/` (`route('home')`). If an authenticated user visits the gateway, they are automatically forwarded to their corresponding portal dashboard.

```mermaid
sequenceDiagram
    autonumber
    actor User as User / Browser
    participant Gateway as LoginGatewayController (/)
    participant Auth as Laravel Auth Guard
    participant Portal as Dedicated Portal

    User->>Gateway: GET /
    Gateway->>Auth: check()
    alt Authenticated
        Auth-->>Gateway: User instance with account_type
        alt account_type == citizen
            Gateway-->>User: 302 Redirect to /citizen/dashboard
        else account_type == personnel
            Gateway-->>User: 302 Redirect to /personnel/availability
        else account_type == office
            Gateway-->>User: 302 Redirect to /office/dashboard
        else account_type == admin
            Gateway-->>User: 302 Redirect to /admin/dashboard
        end
    else Unauthenticated
        Gateway-->>User: 200 OK (Render Portal Selection Gateway)
    end
```

---

## 2. Dedicated Portal Login Workflow

Each account type accesses its dedicated login view (`/citizen/login`, `/personnel/login`, `/office/login`, `/admin/login`).

```mermaid
sequenceDiagram
    autonumber
    actor User as Responder / User
    participant Login as LoginController (POST /{portal}/login)
    participant Limiter as RateLimiter
    participant DB as MariaDB (users, presence, audit_logs)

    User->>Login: Submit credentials (login, password, remember)
    Login->>Limiter: Check attempts for key (login|ip)
    alt Rate limit exceeded (>= 5 failures)
        Limiter-->>Login: Throttled (seconds remaining)
        Login-->>User: 302 Redirect Back with throttle error
    else Within limit
        Login->>DB: Query user by email OR phone_number
        alt User not found or Hash::check fails
            Login->>Limiter: hit(key)
            Login->>DB: Log auth:failed to audit_logs
            Login-->>User: 302 Redirect Back with "Invalid credentials."
        else Credentials valid
            alt user.is_active == false
                Login-->>User: 302 Redirect Back with "Account deactivated."
            else user.account_type != current_portal
                Login->>Limiter: hit(key)
                Login-->>User: 302 Redirect Back with "Access denied for this portal."
            else Access authorized
                Login->>Limiter: clear(key)
                Login->>DB: Update user_presence (online, IP, heartbeat)
                Login->>DB: Insert audit_logs (auth:login)
                alt portal == personnel
                    Login-->>User: 302 Redirect to /personnel/availability
                else other portals
                    Login-->>User: 302 Redirect to /{portal}/dashboard
                end
            end
        end
    end
```

---

## 3. Citizen Registration & Mandatory Location Pinning

```mermaid
sequenceDiagram
    autonumber
    actor Citizen as New Citizen
    participant RegCtrl as CitizenRegisterController (POST /citizen/register)
    participant Validator as CitizenRegisterRequest
    participant DB as MariaDB Transaction

    Citizen->>RegCtrl: Submit Registration Form
    Note over Citizen,RegCtrl: Includes first/last name, phone, password, barangay_id, lat, lng
    RegCtrl->>Validator: Validate rules (unique phone, mandatory lat/lng, password rules)
    alt Validation fails
        Validator-->>Citizen: 302 Redirect Back with validation errors
    else Validation passes
        RegCtrl->>DB: Begin Database Transaction
        DB->>DB: Create User record (account_type: citizen, is_active: true)
        DB->>DB: Attach Role 'citizen'
        DB->>DB: Create CitizenProfile record
        DB->>DB: Create CitizenAddress (is_primary: true, lat/lng, barangay_id)
        DB->>DB: Create UserPresence (status: online)
        DB->>DB: Create UserNotificationSetting
        DB->>DB: Log auth:register to audit_logs
        DB->>RegCtrl: Transaction Committed
        RegCtrl->>RegCtrl: Auth::login($user)
        RegCtrl-->>Citizen: 302 Redirect to /citizen/dashboard
    end
```

---

## 4. Personnel Availability Lifecycle

1. **Login:** Personnel enters valid credentials at `/personnel/login`.
2. **Intermediate Gateway:** Routed directly to `/personnel/availability`.
3. **Selection:**
   - **ONLINE (`available`):** Sets status to `available`, opens a new record in `availability_sessions` with `shift_started_at`. Responders are now discoverable for emergency dispatches.
   - **OFFLINE (`off_duty`):** Sets status to `off_duty`, closes any active session in `availability_sessions` with `shift_ended_at`.
4. **Logout:** If personnel signs out via `POST /logout`, the `LoginController` automatically sets their availability to `off_duty` to ensure units are never left as ghost responders.

