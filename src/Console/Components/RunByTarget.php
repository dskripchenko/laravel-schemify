<?php


namespace Dskripchenko\Schemify\Console\Components;


use Dskripchenko\Schemify\Facades\SchemifyConnector;
use Illuminate\Support\Arr;

trait RunByTarget
{
    public function runByTarget(\Closure $callback)
    {
        $connectionName = config('database.schemify');
        $target = $this->input->getOption('target');
        if ($target === 'main') {
            $callback($this, $this->option('database'));
            return;
        }

        if ($target === 'schemify') {
            foreach (SchemifyConnector::getAllConnectors() as $connector) {
                $connector->refreshConnection($connectionName);
                $callback($this, $connectionName);
            }
            return;
        }

        if (preg_match('/schemify:(?<id>[\d]+)/', $target, $matches)) {
            if (!$connector = SchemifyConnector::getConnectorById(Arr::get($matches, 'id'))) {
                throw new \Exception('The connector is not installed to which the command is applied');
            }
            $connector->refreshConnection($connectionName);
            $callback($this, $connectionName);
            return;
        }
    }
}
