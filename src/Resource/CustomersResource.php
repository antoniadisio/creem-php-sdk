<?php

declare(strict_types=1);

namespace Creem\Resource;

use Creem\Dto\Common\Page;
use Creem\Dto\Customer\CreateCustomerBillingPortalLinkRequest;
use Creem\Dto\Customer\Customer;
use Creem\Dto\Customer\CustomerLinks;
use Creem\Dto\Customer\ListCustomersRequest;
use Creem\Internal\Http\Requests\Customers\GenerateCustomerLinksRequest;
use Creem\Internal\Http\Requests\Customers\ListCustomersRequest as ListCustomersOperation;
use Creem\Internal\Http\Requests\Customers\RetrieveCustomerRequest;
use Creem\Internal\Hydration\Payload;

final class CustomersResource extends Resource
{
    /**
     * @return Page<Customer>
     */
    public function list(?ListCustomersRequest $request = null): Page
    {
        $request ??= new ListCustomersRequest;

        return Payload::page(
            $this->send(new ListCustomersOperation($request->toQuery())),
            static fn (array $item): Customer => Customer::fromPayload($item),
        );
    }

    public function get(string $id): Customer
    {
        return Customer::fromPayload($this->send(new RetrieveCustomerRequest($id)));
    }

    public function findByEmail(string $email): Customer
    {
        return Customer::fromPayload($this->send(new RetrieveCustomerRequest(null, $email)));
    }

    public function createBillingPortalLink(CreateCustomerBillingPortalLinkRequest $request): CustomerLinks
    {
        return CustomerLinks::fromPayload($this->send(new GenerateCustomerLinksRequest($request->toArray())));
    }
}
