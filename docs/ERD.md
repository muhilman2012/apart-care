# ERD APART-CARE

Diagram ini menggambarkan skema domain operasional. Tabel infrastruktur Laravel seperti `cache`, `jobs`, `sessions`, dan `password_reset_tokens` tidak ditampilkan agar relasi bisnis tetap terbaca.

```mermaid
erDiagram
    BUILDINGS ||--o{ TOWERS : memiliki
    BUILDINGS {
        bigint id PK
        varchar code UK
        varchar name
        text address
        boolean is_active
    }
    TOWERS ||--o{ QR_LOCATIONS : memuat
    TOWERS {
        bigint id PK
        bigint building_id FK
        varchar code
        varchar name
        tinyint total_floors
        boolean is_active
    }

    USERS ||--o{ UNITS : menghuni
    USERS ||--o{ TICKETS : melaporkan
    USERS ||--o{ WORK_ORDERS : menerbitkan_TRO
    USERS ||--o{ WORK_ORDERS : mengerjakan_teknisi
    USERS ||--o{ REVIEWS : memberi_ulasan
    USERS ||--o{ TICKET_ATTACHMENTS : mengunggah
    USERS ||--o{ TICKET_STATUS_LOGS : mengubah
    USERS ||--o{ NOTIFICATIONS : menerima_polimorfik
    USERS {
        bigint id PK
        varchar name
        varchar email UK
        varchar password
        enum role
        varchar phone_number
        boolean is_active
    }

    UNITS ||--o{ TICKETS : lokasi_unit
    UNITS {
        bigint id PK
        bigint tenant_user_id FK
        varchar tower_name
        tinyint floor_number
        varchar unit_code UK
        enum occupancy_status
        varchar qr_token UK
    }

    FACILITIES ||--o{ TICKETS : lokasi_fasilitas_lama
    FACILITIES {
        bigint id PK
        varchar facility_code UK
        varchar facility_name
        enum category
        varchar tower_name
        tinyint floor_number
        varchar qr_token UK
    }

    QR_LOCATIONS ||--o{ TICKETS : sumber_scan
    QR_LOCATIONS {
        bigint id PK
        bigint tower_id FK
        uuid uuid UK
        varchar code UK
        varchar name
        enum type
        varchar tower_name
        tinyint floor_number
        varchar zone
        text description
        varchar reference_image_path
        text installation_notes
        boolean is_active
    }

    SLA_POLICIES {
        bigint id PK
        varchar priority_level UK
        varchar name
        int response_minutes
        int resolution_minutes
        varchar escalation_role
        boolean is_active
    }

    TICKETS ||--o| WORK_ORDERS : menghasilkan
    TICKETS ||--o{ SLA_LOGS : diaudit
    TICKETS ||--o| REVIEWS : dinilai
    TICKETS ||--o{ TICKET_ATTACHMENTS : memiliki
    TICKETS ||--o{ TICKET_STATUS_LOGS : memiliki
    TICKETS ||--o{ NOTIFICATIONS : memicu
    TICKETS {
        bigint id PK
        bigint reporter_user_id FK
        bigint unit_id FK
        bigint facility_id FK
        bigint qr_location_id FK
        varchar public_token UK
        varchar ticket_number UK
        enum ticket_type
        varchar title
        text issue_description
        enum priority_level
        enum status
        timestamp target_sla_at
    }

    WORK_ORDERS {
        bigint id PK
        bigint ticket_id FK,UK
        bigint tro_user_id FK
        bigint technician_user_id FK
        text dispatch_notes
        varchar before_photo_path
        varchar after_photo_path
        text technician_notes
        timestamp dispatched_at
        timestamp started_at
        timestamp completed_at
    }

    SLA_LOGS {
        bigint id PK
        bigint ticket_id FK
        enum breach_status
        int resolution_duration_minutes
        timestamp escalated_at
        varchar escalation_target_role
    }

    REVIEWS {
        bigint id PK
        bigint ticket_id FK,UK
        bigint resident_user_id FK
        tinyint rating_stars
        text review_comment
        timestamp verified_at
    }

    TICKET_ATTACHMENTS {
        bigint id PK
        bigint ticket_id FK
        bigint uploaded_by_user_id FK
        enum kind
        varchar file_path
        varchar mime_type
        bigint file_size
    }

    TICKET_STATUS_LOGS {
        bigint id PK
        bigint ticket_id FK
        bigint changed_by_user_id FK
        varchar from_status
        varchar to_status
        text notes
        timestamp changed_at
    }

    NOTIFICATIONS {
        uuid id PK
        varchar type
        varchar notifiable_type
        bigint notifiable_id
        bigint ticket_id FK
        text data
        timestamp read_at
    }
```

## Catatan desain

- `qr_locations.uuid` adalah identitas publik dalam URL dan tidak berubah setelah label dibuat.
- `qr_locations.code` adalah kode pendek yang dicetak di bawah QR sebagai jalur manual ketika kamera gagal.
- `reference_image_path` menampilkan foto/ilustrasi objek agar pelapor dapat memverifikasi lokasi sebelum mengirim laporan.
- `ticket_status_logs` menyimpan audit transisi status, sedangkan `sla_logs` menyimpan hasil evaluasi SLA.
- `ticket_attachments` memungkinkan lebih dari satu bukti foto tanpa menghilangkan kolom kompatibilitas pada tiket dan work order.
