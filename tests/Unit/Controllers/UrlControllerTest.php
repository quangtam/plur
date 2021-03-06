<?php

namespace Tests\Unit\Controllers;

use App\Rules\Lowercase;
use App\Rules\URL\ShortUrlProtected;
use App\Url;
use App\UrlStat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * @coversDefaultClass App\Http\Controllers\UrlController
 */
class UrlControllerTest extends TestCase
{
    /**
     * The user_id column in the table of Url must be null.
     *
     * @test
     * @covers ::create
     */
    public function shortenUrl_user_id()
    {
        $long_url = 'https://laravel.com';

        $response = $this->post(route('createshortlink'), [
            'long_url' => $long_url,
        ]);

        $url = Url::whereLongUrl($long_url)->first();

        $this->assertSame(null, $url->user_id);
    }

    /**
     * The user_id column in the table of Url must be filled in with the value
     * of the authenticated user id.
     *
     * @test
     * @covers ::create
     */
    public function shortenUrl_user_id_2()
    {
        $user = $this->admin();
        $long_url = 'https://laravel.com';

        $this->loginAsAdmin();

        $response = $this->post(route('createshortlink'), [
            'long_url' => $long_url,
        ]);

        $url = Url::whereLongUrl($long_url)->first();

        $this->assertSame($user->id, $url->user_id);
    }

    /**
     * The url_key column in the table of Url must be filled with the value
     * generated by key_generator().
     *
     * @test
     * @covers ::create
     */
    public function shortenUrl_url_key()
    {
        $long_url = 'https://laravel.com';

        $response = $this->post(route('createshortlink'), [
            'long_url' => $long_url,
        ]);

        $url = Url::whereLongUrl($long_url)->first();

        $this->assertEquals(
            config('urlhub.hash_size_1'),
            strlen($url->url_key)
        );
    }

    /**
     * The url_key column in the table of Url must be filled in with the value
     * given by User.
     *
     * @test
     * @covers ::create
     */
    public function shortenUrl_url_key_2()
    {
        config()->set('urlhub.hash_size_1', 6);

        $long_url = 'https://laravel.com';
        $custom_url = 'laravel-http-tests';

        $response = $this->post(route('createshortlink'), [
            'long_url'       => $long_url,
            'custom_url_key' => $custom_url,
        ]);

        $this->assertGreaterThan(
            config('urlhub.hash_size_1'),
            strlen($custom_url)
        );
    }

    /**
     * @test
     * @covers ::create
     */
    public function shortenUrl_is_custom()
    {
        $long_url = 'https://laravel.com';

        $response = $this->post(route('createshortlink'), [
            'long_url' => $long_url,
        ]);

        $url = Url::whereLongUrl($long_url)->first();

        $this->assertFalse($url->is_custom);
    }

    /**
     * @test
     * @covers ::create
     */
    public function shortenUrl_is_custom_2()
    {
        $long_url = 'https://laravel.com';

        $response = $this->post(route('createshortlink'), [
            'long_url'       => $long_url,
            'custom_url_key' => 'laravel',
        ]);

        $url = Url::whereLongUrl($long_url)->first();

        $this->assertTrue($url->is_custom);
    }

    /**
     * @test
     * @covers ::urlRedirection
     */
    public function url_redirection()
    {
        $url = factory(Url::class)->create();

        $response = $this->get(route('home').'/'.$url->url_key);
        $response->assertRedirect($url->long_url);
        $response->assertStatus(301);
    }

    /**
     * URL statistic check.
     *
     * @test
     * @covers ::urlRedirection
     */
    public function url_redirection_2()
    {
        $url = factory(Url::class)->create();

        $response = $this->get(route('home').'/'.$url->url_key);
        $this->assertCount(1, UrlStat::all());
    }

    /**
     * @test
     * @covers ::checkExistingCustomUrl
     */
    public function check_existing_custom_url_pass()
    {
        $response = $this->post(route('home').'/custom-link-avail-check', [
            'url_key' => 'hello',
        ]);

        $response->assertJson(['success'=>'Available']);
    }

    /**
     * @test
     * @covers ::checkExistingCustomUrl
     * @dataProvider checkExistingCustomUrl_fail
     */
    public function check_existing_custom_url_fail($data)
    {
        factory(Url::class)->create([
            'url_key' => 'laravel',
        ]);

        $request = new Request;

        $validator = Validator::make($request->all(), [
            'url_key'  => ['max:20', 'alpha_dash', 'unique:urls', new Lowercase, new ShortUrlProtected],
        ]);

        $response = $this->post(route('home').'/custom-link-avail-check', [
            'url_key' => $data,
        ]);

        $response->assertJson(['errors'=>$validator->errors()->all()]);
    }

    public function checkExistingCustomUrl_fail()
    {
        return [
            [str_repeat('a', 50)],
            ['laravel~'],
            ['laravel'],
            ['Laravel'],
            ['login'],
        ];
    }
}
