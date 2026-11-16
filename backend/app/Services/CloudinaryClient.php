<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class CloudinaryClient
{
    public function uploadAvatar(int $userId, UploadedFile $file)
    {
        return Http::timeout(30)
            ->attach('file', $file->get(), $file->getClientOriginalName())
            ->post($this->url('upload'), $this->signed(['public_id' => $this->avatarId($userId)]))
            ->throw()
            ->json('secure_url');
    }

    public function deleteAvatar(int $userId)
    {
        Http::timeout(10)
            ->asForm()
            ->post($this->url('destroy'), $this->signed(['public_id' => $this->avatarId($userId), 'invalidate' => 'true']))
            ->throw();
    }

    private function avatarId(int $userId)
    {
        return "avatars/user_$userId";
    }

    private function url(string $action)
    {
        return 'https://api.cloudinary.com/v1_1/' . config('services.cloudinary.cloud_name') . "/image/$action";
    }

    private function signed(array $params)
    {
        $params['timestamp'] = time();
        ksort($params);

        $params['signature'] = sha1(urldecode(http_build_query($params)) . config('services.cloudinary.secret'));
        $params['api_key'] = config('services.cloudinary.key');

        return $params;
    }
}
