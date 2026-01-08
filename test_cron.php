<?php

require 'vendor/autoload.php';

use Grazulex\AutoBuilder\BuiltIn\Triggers\OnSchedule;
use Cron\CronExpression;
use DateTimeZone;

$trigger = new OnSchedule();
$trigger->configure([
    'frequency' => 'everyMinute',
    'timezone' => 'UTC',
]);

$cronExpression = $trigger->getCronExpression();
echo 'Cron: ' . $cronExpression . PHP_EOL;

$timezone = new DateTimeZone('UTC');
$cron = new CronExpression($cronExpression);
echo 'Is due: ' . ($cron->isDue('now', $timezone) ? 'yes' : 'no') . PHP_EOL;
