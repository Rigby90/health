<?php

namespace PragmaRX\Health\Tests\PhpUnit\Service;

use Illuminate\Support\Collection;
use PragmaRX\Health\Facade as Health;
use PragmaRX\Health\Tests\PhpUnit\TestCase;

class ServiceTest extends TestCase
{
    const ALL_RESOURCES = [
        'health',
        'broadcasting',
        'cache',
        'database',
        'docusign',
        'filesystem',
        'framework',
        'http',
        'https',
        'laravelservices',
        'localstorage',
        'mail',
        'mysql',
        'newrelicdeamon',
        'nginxserver',
        'php',
        'postgresqlserver',
        'queue',
        'queueworkers',
        'rebootrequired',
        'redis',
        'redisserver',
        's3',
        'serverload',
        'serveruptime',
        'sshd',
        'supervisor',
    ];

    const RESOURCES_FAILING = [
        'Health',
        'Broadcasting',
        'Database',
        'DocuSign',
        'Http',
        'Https',
        'NewrelicDeamon',
        'Redis',
        'S3',
    ];

    /**
     * @var \PragmaRX\Health\Service
     */
    private $service;

    /**
     * @var \Illuminate\Support\Collection
     */
    private $resources;

    private function getConfig()
    {
        $config = require __DIR__.'/config.php';

        return $config;
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('health.resources_location.path', package_resources_dir());
    }

    public function setUp()
    {
        parent::setUp();

        $this->service = app('pragmarx.health');

        $this->resources = $this->service->checkResources();
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(Collection::class, $this->resources);
    }

    public function testConfigWasLoadedProperly()
    {
        $this->assertEquals($this->resources['Health']['error_message'], 'At least one resource failed the health check.');
    }

    public function testResourcesHasTheCorrectCount()
    {
        $this->assertCount(count(static::ALL_RESOURCES), $this->resources->toArray());
    }

    public function testResourcesItemsMatchConfig()
    {
        $this->assertEquals(
            static::ALL_RESOURCES,
            $this->resources->keys()->map(function ($value) {
                return strtolower($value);
            })->toArray()
        );
    }

    public function testResourcesWhereChecked()
    {
        $healthCount = $this->resources->reduce(function ($carry, $item) {
            return $carry + (isset($item['health']['healthy'])
                    ? 1
                    : 0);
        }, 0);

        $this->assertEquals(count(static::ALL_RESOURCES), $healthCount);

        $this->assertEquals(
            0,
            $this->resources
                ->reject(function ($item) {
                    return $item['health']['healthy'];
                })
                ->keys()
                ->diff(static::RESOURCES_FAILING)->count()
        );
    }
}
