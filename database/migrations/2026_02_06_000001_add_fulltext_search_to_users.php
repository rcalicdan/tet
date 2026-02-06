<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE users 
            ADD COLUMN searchable tsvector 
            GENERATED ALWAYS AS (
                setweight(to_tsvector('english', coalesce(first_name, '')), 'A') ||
                setweight(to_tsvector('english', coalesce(last_name, '')), 'A') ||
                setweight(to_tsvector('english', coalesce(email, '')), 'B') ||
                setweight(to_tsvector('english', coalesce(bio, '')), 'C') ||
                setweight(to_tsvector('english', coalesce(city, '')), 'B')
            ) STORED
        ");

        DB::statement("CREATE INDEX users_searchable_idx ON users USING GIN (searchable)");

        DB::statement("CREATE EXTENSION IF NOT EXISTS pg_trgm");
        DB::statement("CREATE INDEX users_first_name_trgm_idx ON users USING GIN (first_name gin_trgm_ops)");
        DB::statement("CREATE INDEX users_last_name_trgm_idx ON users USING GIN (last_name gin_trgm_ops)");
        DB::statement("CREATE INDEX users_city_trgm_idx ON users USING GIN (city gin_trgm_ops)");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS users_searchable_idx");
        DB::statement("DROP INDEX IF EXISTS users_first_name_trgm_idx");
        DB::statement("DROP INDEX IF EXISTS users_last_name_trgm_idx");
        DB::statement("DROP INDEX IF EXISTS users_city_trgm_idx");
        DB::statement("ALTER TABLE users DROP COLUMN IF EXISTS searchable");
    }
};