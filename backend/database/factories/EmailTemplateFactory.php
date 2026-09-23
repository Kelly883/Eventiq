<?php

namespace Database\Factories;

use App\Features\EmailNotifications\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailTemplate>
 */
class EmailTemplateFactory extends Factory
{
    protected $model = EmailTemplate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company . ' ' . $this->faker->word,
            'type' => $this->faker->randomElement([
                'order_confirmation', 'event_reminder', 'ticket_delivery',
                'check_in_confirmation', 'refund_notification',
            ]),
            'subject' => 'Your ' . $this->faker->word . ' receipt',
            'from_name' => $this->faker->company,
            'from_email' => $this->faker->companyEmail,
            'mjml_body' => '<mjml><mj-body><mj-section><mj-column><mj-text>Hello {{name}}</mj-text></mj-column></mj-section></mj-body></mjml>',
            'html_body' => '<p>Hello {{name}}</p>',
            'variables' => ['name'],
            'is_active' => $this->faker->boolean(80),
            'category' => $this->faker->word,
            'description' => $this->faker->sentence,
        ];
    }
}
