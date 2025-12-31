<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Triggers\OnEventDispatched;
use Grazulex\AutoBuilder\BuiltIn\Triggers\OnManualTrigger;
use Grazulex\AutoBuilder\BuiltIn\Triggers\OnModelCreated;
use Grazulex\AutoBuilder\BuiltIn\Triggers\OnModelDeleted;
use Grazulex\AutoBuilder\BuiltIn\Triggers\OnModelUpdated;
use Grazulex\AutoBuilder\BuiltIn\Triggers\OnQueueJobFailed;
use Grazulex\AutoBuilder\BuiltIn\Triggers\OnSchedule;
use Grazulex\AutoBuilder\BuiltIn\Triggers\OnWebhookReceived;

describe('OnModelCreated', function () {
    it('has correct metadata', function () {
        $trigger = new OnModelCreated();

        expect($trigger->name())->toBe('Model Created');
        expect($trigger->type())->toBe('trigger');
        expect($trigger->category())->toBe('Database');
        expect($trigger->icon())->toBe('database-plus');
        expect($trigger->description())->toContain('created');
    });

    it('has fields configuration', function () {
        $trigger = new OnModelCreated();
        $fields = $trigger->fields();

        expect($fields)->not->toBeEmpty();
        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);
        expect($fieldNames)->toContain('model');
    });
});

describe('OnModelUpdated', function () {
    it('has correct metadata', function () {
        $trigger = new OnModelUpdated();

        expect($trigger->name())->toBe('Model Updated');
        expect($trigger->type())->toBe('trigger');
        expect($trigger->category())->toBe('Database');
        expect($trigger->icon())->toBe('database-edit');
    });

    it('has watch_fields option', function () {
        $trigger = new OnModelUpdated();
        $fields = $trigger->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);
        expect($fieldNames)->toContain('watch_fields');
    });
});

describe('OnModelDeleted', function () {
    it('has correct metadata', function () {
        $trigger = new OnModelDeleted();

        expect($trigger->name())->toBe('Model Deleted');
        expect($trigger->type())->toBe('trigger');
        expect($trigger->category())->toBe('Database');
        expect($trigger->icon())->toBe('database-x');
    });

    it('has include_soft_deletes option', function () {
        $trigger = new OnModelDeleted();
        $fields = $trigger->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);
        expect($fieldNames)->toContain('include_soft_deletes');
    });
});

describe('OnSchedule', function () {
    it('has correct metadata', function () {
        $trigger = new OnSchedule();

        expect($trigger->name())->toBe('Scheduled');
        expect($trigger->type())->toBe('trigger');
        expect($trigger->category())->toBe('Time');
        expect($trigger->icon())->toBe('clock');
    });

    it('has cron expression field', function () {
        $trigger = new OnSchedule();
        $fields = $trigger->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);
        expect($fieldNames)->toContain('cron');
    });
});

describe('OnWebhookReceived', function () {
    it('has correct metadata', function () {
        $trigger = new OnWebhookReceived();

        expect($trigger->name())->toBe('Webhook Received');
        expect($trigger->type())->toBe('trigger');
        expect($trigger->category())->toBe('External');
        expect($trigger->icon())->toBe('webhook');
    });
});

describe('OnEventDispatched', function () {
    it('has correct metadata', function () {
        $trigger = new OnEventDispatched();

        expect($trigger->name())->toBe('Event Dispatched');
        expect($trigger->type())->toBe('trigger');
        expect($trigger->category())->toBe('Application');
        expect($trigger->icon())->toBe('zap');
    });

    it('has event_class field', function () {
        $trigger = new OnEventDispatched();
        $fields = $trigger->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);
        expect($fieldNames)->toContain('event');
    });
});

describe('OnQueueJobFailed', function () {
    it('has correct metadata', function () {
        $trigger = new OnQueueJobFailed();

        expect($trigger->name())->toBe('Queue Job Failed');
        expect($trigger->type())->toBe('trigger');
        expect($trigger->category())->toBe('Application');
        expect($trigger->icon())->toBe('alert-triangle');
    });
});

describe('OnManualTrigger', function () {
    it('has correct metadata', function () {
        $trigger = new OnManualTrigger();

        expect($trigger->name())->toBe('Manual Trigger');
        expect($trigger->type())->toBe('trigger');
        expect($trigger->category())->toBe('Manual');
        expect($trigger->icon())->toBe('play');
    });
});
