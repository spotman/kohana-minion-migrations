<?php

declare(strict_types=1);

namespace BetaKiller\Migration;

interface MigrationInterface
{
    /**
     * Returns migration ID
     *
     * @return int
     */
    public function id(): int;

    /**
     * Returns migration name
     *
     * @return string
     */
    public function name(): string;

    /**
     * Returns migration description
     *
     * @return string
     */
    public function description(): string;

    /**
     * Takes a migration
     *
     * @return void
     */
    public function up(): void;

    /**
     * Removes migration
     *
     * @return void
     */
    public function down(): void;

    /**
     * This method will be executed before up migration starts
     * You may put additional checks here
     */
    public function beforeUp(): void;

    /**
     * This method will be executed before down migration starts
     * You may put additional checks here
     */
    public function beforeDown(): void;

    /**
     * This method will be executed after up migration ends
     * You may put here finalizing/cleanup statements
     */
    public function afterUp(): void;

    /**
     * This method will be executed after down migration ends
     * You may put here finalizing/cleanup statements
     */
    public function afterDown(): void;
}
