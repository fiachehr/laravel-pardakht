<?php

namespace Fiachehr\Pardakht\Tests\Unit;

use Fiachehr\Pardakht\Tests\TestCase;
use Fiachehr\Pardakht\Manager\GatewayManager;
use Fiachehr\Pardakht\Contracts\TransactionRepositoryInterface;
use Fiachehr\Pardakht\Repositories\TransactionRepository;

class PardakhtServiceProviderTest extends TestCase
{
    /** @test */
    public function service_provider_registers_gateway_manager()
    {
        $manager = app('pardakht');

        $this->assertInstanceOf(GatewayManager::class, $manager);
    }

    /** @test */
    public function service_provider_registers_gateway_manager_as_singleton()
    {
        $manager1 = app('pardakht');
        $manager2 = app('pardakht');

        $this->assertSame($manager1, $manager2);
    }

    /** @test */
    public function service_provider_registers_transaction_repository()
    {
        $repository = app(TransactionRepositoryInterface::class);

        $this->assertInstanceOf(TransactionRepositoryInterface::class, $repository);
        $this->assertInstanceOf(TransactionRepository::class, $repository);
    }

    /** @test */
    public function service_provider_binds_gateway_manager_class()
    {
        $manager = app(GatewayManager::class);

        $this->assertInstanceOf(GatewayManager::class, $manager);
    }

    /** @test */
    public function config_is_loaded_correctly()
    {
        $this->assertIsArray(config('pardakht'));
        $this->assertArrayHasKey('default', config('pardakht'));
        $this->assertArrayHasKey('gateways', config('pardakht'));
        $this->assertArrayHasKey('store_transactions', config('pardakht'));
    }

    /** @test */
    public function default_gateway_configuration_exists()
    {
        $defaultGateway = config('pardakht.default');
        $gateways = config('pardakht.gateways');

        $this->assertNotEmpty($defaultGateway);
        $this->assertArrayHasKey($defaultGateway, $gateways);
    }

    /** @test */
    public function all_required_gateways_are_configured()
    {
        $gateways = config('pardakht.gateways');

        $this->assertArrayHasKey('mellat', $gateways);
        $this->assertArrayHasKey('mabna', $gateways);
        $this->assertArrayHasKey('zarinpal', $gateways);
    }

    /** @test */
    public function gateway_configurations_have_required_keys()
    {
        $mellatConfig = config('pardakht.gateways.mellat');

        $this->assertArrayHasKey('driver', $mellatConfig);
        $this->assertArrayHasKey('terminal_id', $mellatConfig);
        $this->assertArrayHasKey('callback_url', $mellatConfig);
        $this->assertArrayHasKey('sandbox', $mellatConfig);
    }
}
