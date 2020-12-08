<?php

namespace Dskripchenko\Schemify\Console\Migrations;

use Dskripchenko\Schemify\Console\Components\PathByTarget;
use Dskripchenko\Schemify\Console\Components\RunByTarget;

class MigrateMakeCommand extends \Illuminate\Database\Console\Migrations\MigrateMakeCommand
{
    use PathByTarget, RunByTarget;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'make:migration {name : The name of the migration}
        {--create= : The table to be created}
        {--table= : The table to migrate}
        {--path= : The location where the migration file should be created}
        {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
        {--fullpath : Output the full path of the migration}
        {--target=main : The purpose of the command. Available values are main schema, client schemas. (main|schemify)}';
}
