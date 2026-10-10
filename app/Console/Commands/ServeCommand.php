<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand as FrameworkServeCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'serve')]
class ServeCommand extends FrameworkServeCommand
{
    /**
     * Laravel's environment hot-reload subprocess cannot bind reliably on
     * Windows with PHP 8.5. Make the safe --no-reload behavior the default.
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if (PHP_OS_FAMILY === 'Windows' && ! $input->getOption('no-reload')) {
            $input->setOption('no-reload', true);
        }

        parent::initialize($input, $output);
    }
}
