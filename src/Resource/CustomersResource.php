<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Resource;

use Antoniadisio\Creem\Dto\Common\Page;
use Antoniadisio\Creem\Dto\Customer\CreateCustomerBillingPortalLinkRequest;
use Antoniadisio\Creem\Dto\Customer\Customer;
use Antoniadisio\Creem\Dto\Customer\CustomerLinks;
use Antoniadisio\Creem\Dto\Customer\ListCustomersRequest;
use Antoniadisio\Creem\Internal\Http\Requests\Customers\GenerateCustomerLinksRequest;
use Antoniadisio\Creem\Internal\Http\Requests\Customers\ListCustomersRequest as ListCustomersOperation;
use Antoniadisio\Creem\Internal\Http\Requests\Customers\RetrieveCustomerRequest;
use Antoniadisio\Creem\Internal\Hydration\Payload;

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

    public function createBillingPortalLink(CreateCustomerBillingPortalLinkRequest $request, ?string $idempotencyKey = null): CustomerLinks
    {
        return CustomerLinks::fromPayload($this->send(new GenerateCustomerLinksRequest($request->toArray(), $idempotencyKey)));
    }
}
