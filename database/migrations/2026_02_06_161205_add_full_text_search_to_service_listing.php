<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE service_listings 
            ADD COLUMN searchable tsvector 
            GENERATED ALWAYS AS (
                setweight(to_tsvector('english', coalesce(service_type, '')), 'A') ||
                setweight(to_tsvector('english', coalesce(description, '')), 'B') ||
                setweight(to_tsvector('english', coalesce(service_city, '')), 'A')
            ) STORED
        ");

        DB::statement("CREATE INDEX service_listings_searchable_idx ON service_listings USING GIN (searchable)");
        DB::statement("CREATE EXTENSION IF NOT EXISTS pg_trgm");
        DB::statement("CREATE INDEX service_listings_service_type_trgm_idx ON service_listings USING GIN (service_type gin_trgm_ops)");
        DB::statement("CREATE INDEX service_listings_service_city_trgm_idx ON service_listings USING GIN (service_city gin_trgm_ops)");
        DB::statement("CREATE INDEX service_listings_description_trgm_idx ON service_listings USING GIN (description gin_trgm_ops)");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS service_listings_searchable_idx");
        DB::statement("DROP INDEX IF EXISTS service_listings_service_type_trgm_idx");
        DB::statement("DROP INDEX IF EXISTS service_listings_service_city_trgm_idx");
        DB::statement("DROP INDEX IF EXISTS service_listings_description_trgm_idx");
        DB::statement("ALTER TABLE service_listings DROP COLUMN IF EXISTS searchable");
    }
};