<?php

namespace App\Support;

use App\Models\Shop;

/**
 * The customer emails a shop sends (Pro and Complete): the wording of the order email and the
 * review request. Layout is fixed; only this text changes. Stored on shops.emails.
 *
 * {first_name}, {order_number} and {shop_name} are filled in when each email is sent.
 */
class EmailSettings
{
    public const FIELDS = [
        'order_heading' => 160,
        'order_message' => 1500,
        'order_needs' => 1500,
        'order_next' => 1500,
        'review_heading' => 160,
        'review_message' => 1500,
        'review_url' => 300,
    ];

    public static function defaults(): array
    {
        return [
            'order_on' => true,
            'order_heading' => 'Thanks {first_name}, we\'ve got your order',
            'order_message' => 'We\'re getting your plates ready. Here\'s what happens next and what we need from you.',
            'order_needs' => 'For road-legal plates we need a photo of your ID (such as your driving licence) and proof you can use the registration (such as your V5C logbook). If you didn\'t upload them at checkout, email them to us or bring them when you collect.',
            'order_next' => 'Once we\'ve checked your documents we\'ll make your plates and email you when they\'re ready.',
            'review_on' => true,
            'review_heading' => 'How are your new plates, {first_name}?',
            'review_message' => 'We hope they look great. If you have a minute, a quick review helps other drivers find us.',
            'review_url' => '',
        ];
    }

    public static function forShop(Shop $shop): array
    {
        return array_merge(self::defaults(), array_intersect_key($shop->emails ?? [], self::defaults()));
    }

    public static function rules(): array
    {
        $rules = ['order_on' => ['nullable', 'boolean'], 'review_on' => ['nullable', 'boolean']];
        foreach (self::FIELDS as $key => $max) {
            $rules[$key] = ['nullable', 'string', 'max:'.$max];
        }
        $rules['review_url'] = ['nullable', 'url:https', 'max:300'];
        $rules['order_heading'][] = 'required_if_accepted:order_on';
        $rules['review_heading'][] = 'required_if_accepted:review_on';
        $rules['review_url'][] = 'required_if_accepted:review_on';

        return $rules;
    }

    /** What the website gets: null on Starter (it sends WooCommerce's standard emails). */
    public static function forSite(Shop $shop): ?array
    {
        if (! $shop->hasCustomerEmails()) {
            return null;
        }
        $e = self::forShop($shop);

        return [
            'order' => $e['order_on'] ? array_intersect_key($e, array_flip(['order_heading', 'order_message', 'order_needs', 'order_next'])) : null,
            'review' => $e['review_on'] && $e['review_url'] ? [
                'heading' => $e['review_heading'],
                'message' => $e['review_message'],
                'url' => $e['review_url'],
                'days' => 3,
            ] : null,
        ];
    }

    /** Fill in the placeholders with example values (for previews and test emails). */
    public static function fill(string $text, Shop $shop, string $firstName = 'Sam', string $order = '1042'): string
    {
        return strtr($text, ['{first_name}' => $firstName, '{order_number}' => $order, '{shop_name}' => $shop->trading_name ?: $shop->name]);
    }
}
