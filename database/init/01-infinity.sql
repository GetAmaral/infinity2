-- Infinity Database Initialization
-- PostgreSQL 18 with UUIDv7 Support

-- Enable required extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Verify UUIDv7 support (PostgreSQL 18 feature)
DO $$
BEGIN
    PERFORM uuidv7();
    RAISE NOTICE 'PostgreSQL 18 UUIDv7 support confirmed ✅';
EXCEPTION WHEN OTHERS THEN
    RAISE EXCEPTION 'UUIDv7 not supported. Ensure PostgreSQL 18 is installed.';
END $$;

-- Performance optimizations for development
ALTER SYSTEM SET shared_buffers = '256MB';
ALTER SYSTEM SET effective_cache_size = '1GB';
ALTER SYSTEM SET maintenance_work_mem = '64MB';

-- Apply configuration
SELECT pg_reload_conf();