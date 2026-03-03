<?php

declare(strict_types=1);

namespace Creem\Internal\Http\Requests\Customers;

use Creem\Internal\Http\Requests\QueryRequest;
use Saloon\Enums\Method;

final class RetrieveCustomerRequest extends QueryRequest
{
    protected Method $method = Method::GET;

    public function __construct(?string $customerId = null, ?string $email = null)
    {
        $query = [];

        if ($customerId !== null) {
            $query['customer_id'] = $customerId;
        }

        if ($email !== null) {
            $query['email'] = $email;
        }

        parent::__construct($query);
    }

    public function resolveEndpoint(): string
    {
        return '/v1/customers';
    }
}
