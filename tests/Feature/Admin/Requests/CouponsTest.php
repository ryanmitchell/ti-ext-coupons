<?php

namespace Tests\Feature\Admin\Requests;

use Faker\Factory;
use Requests\Coupon;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use ValidateRequest;

    protected $requestClass = Coupon::class;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function validationProvider()
    {
        /* WithFaker trait doesn't work in the dataProvider */
        $faker = Factory::create(Factory::DEFAULT_LOCALE);

        return [
            'request_should_fail_when_no_name_is_provided' => [
                'passed' => FALSE,
                'data' => $this->exceptValidationData($faker, ['name']),
            ],

            'request_should_fail_when_name_has_less_than_2_characters' => [
                'passed' => FALSE,
                'data' => $this->mergeValidationData($faker, [
                    'name' => $faker->sentence(1),
                ]),
            ],

            'request_should_fail_when_name_has_more_than_255_characters' => [
                'passed' => FALSE,
                'data' => $this->mergeValidationData($faker, [
                    'name' => $faker->sentence(128),
                ]),
            ],

            'request_should_fail_when_no_code_is_provided' => [
                'passed' => FALSE,
                'data' => $this->exceptValidationData($faker, ['code']),
            ],

            'request_should_fail_when_code_has_less_than_2_characters' => [
                'passed' => FALSE,
                'data' => $this->mergeValidationData($faker, [
                    'code' => $faker->sentence(1),
                ]),
            ],

            'request_should_fail_when_no_type_is_provided' => [
                'passed' => FALSE,
                'data' => $this->exceptValidationData($faker, ['type']),
            ],

            'request_should_fail_when_no_discount_is_provided' => [
                'passed' => FALSE,
                'data' => $this->exceptValidationData($faker, ['discount']),
            ],

            'request_should_fail_when_discount_is_no_numeric' => [
                'passed' => FALSE,
                'data' => $this->mergeValidationData($faker, [
                    'discount' => $faker->sentence(1),
                ]),
            ],

            'request_should_fail_when_locations_is_not_an_array_of_integers' => [
                'passed' => FALSE,
                'data' => $this->mergeValidationData($faker, [
                    'locations' => [$faker->word],
                ]),
            ],

            'request_should_fail_when_status_is_not_a_boolean' => [
                'passed' => FALSE,
                'data' => $this->mergeValidationData($faker, [
                    'status' => $faker->word(),
                ]),
            ],

            'request_should_fail_when_order_restriction_is_not_an_integer' => [
                'passed' => FALSE,
                'data' => $this->mergeValidationData($faker, [
                    'order_restriction' => $faker->word(),
                ]),
            ],

            'request_should_fail_when_no_coupon_recurring_from_is_provided' => [
                'passed' => FALSE,
                'data' => $this->mergeValidationData($faker, [
                    'general' => [
                        'validity' => 'recurring',
                        'recurring_every' => 'Mon',
                        'to_time' => $faker->time('H:i'),
                    ],
                ]),
            ],

            'request_should_fail_when_no_coupon_recurring_to_is_provided' => [
                'passed' => FALSE,
                'data' => $this->mergeValidationData($faker, [
                    'general' => [
                        'validity' => 'recurring',
                        'recurring_every' => 'Mon',
                        'from_time' => $faker->time('H:i'),
                    ],
                ]),
            ],

            'request_should_fail_when_no_coupon_start_date_is_provided' => [
                'passed' => FALSE,
                'data' => $this->mergeValidationData($faker, [
                    'general' => [
                        'validity' => 'period',
                        'end_date' => $faker->date(),
                    ],
                ]),
            ],

            'request_should_fail_when_no_coupon_end_date_is_provided' => [
                'passed' => FALSE,
                'data' => $this->mergeValidationData($faker, [
                    'general' => [
                        'validity' => 'period',
                        'start_date' => $faker->date(),
                    ],
                ]),
            ],

            'request_should_fail_when_no_coupon_fixed_date_is_provided' => [
                'passed' => FALSE,
                'data' => $this->mergeValidationData($faker, [
                    'general' => [
                        'validity' => 'fixed',
                        'fixed_from_time' => $faker->time('H:i'),
                        'fixed_to_time' => $faker->time('H:i'),
                    ],
                ]),
            ],

            'request_should_fail_when_no_coupon_fixed_from_time_is_provided' => [
                'passed' => FALSE,
                'data' => $this->mergeValidationData($faker, [
                    'general' => [
                        'validity' => 'fixed',
                        'fixed_to_time' => $faker->time('H:i'),
                        'fixed_date' => $faker->date(),
                    ],
                ]),
            ],

            'request_should_fail_when_no_coupon_fixed_to_time_is_provided' => [
                'passed' => FALSE,
                'data' => $this->mergeValidationData($faker, [
                    'general' => [
                        'validity' => 'fixed',
                        'fixed_from_time' => $faker->time('H:i'),
                        'fixed_date' => $faker->date(),
                    ],
                ]),
            ],
        ];
    }
}
