<?php

namespace Dskripchenko\Schemify\Console\Migrations;

use Dskripchenko\Schemify\Traits\PathByLayer;
use Dskripchenko\Schemify\Traits\RunByLayer;
use \Illuminate\Database\Console\Migrations\MigrateMakeCommand as BaseMigrateMakeCommand;

/**
 * Class MigrateMakeCommand
 * @package Dskripchenko\Schemify\Console\Migrations
 */
class MigrateMakeCommand extends BaseMigrateMakeCommand
{
    use PathByLayer, RunByLayer;

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
        {--layer=main : Слой к которому применяется команда.}';
}
