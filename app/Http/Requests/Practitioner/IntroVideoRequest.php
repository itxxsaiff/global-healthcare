<?php

namespace App\Http\Requests\Practitioner;

use App\Models\PractitionerProfile;
use App\Models\Setting;
use App\Services\Media\VideoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Optional intro video: either a YouTube/Vimeo link or a short uploaded clip. */
class IntroVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->practitionerProfile?->verification_status->isEditable() ?? false;
    }

    public function rules(): array
    {
        $maxKb = (int) Setting::get('intro_video_max_mb', 50) * 1024;

        return [
            'source' => ['required', Rule::in(['link', 'upload'])],
            'intro_video_url' => ['exclude_unless:source,link', 'required', 'url:https', 'max:255'],
            'video' => [
                'exclude_unless:source,upload', 'required', 'file',
                'mimes:mp4,webm,mov', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:'.$maxKb,
            ],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            if ($this->input('source') === 'link' && ! PractitionerProfile::parseVideoUrl($this->input('intro_video_url'))) {
                $validator->errors()->add('intro_video_url', 'Please paste a YouTube or Vimeo video link (for example https://youtu.be/…).');
            }
            if ($this->input('source') === 'upload') {
                $max = (int) Setting::get('intro_video_max_seconds', 120);
                $seconds = app(VideoService::class)->duration($this->file('video'));
                if ($seconds === null) {
                    $validator->errors()->add('video', 'We could not read this video. Please export it as MP4 and try again.');
                } elseif ($seconds > $max + 1) {
                    $validator->errors()->add('video', 'Your video is '.gmdate('i:s', (int) round($seconds)).' long. Please keep it under '.intdiv($max, 60).' minutes.');
                }
            }
        }];
    }

    public function messages(): array
    {
        $mb = (int) Setting::get('intro_video_max_mb', 50);

        return [
            'intro_video_url.required' => 'Please paste your YouTube or Vimeo link.',
            'intro_video_url.url' => 'Please paste a full https:// link.',
            'video.required' => 'Please choose a video file.',
            'video.uploaded' => "The upload didn't finish. Please use a video under {$mb} MB.",
            'video.max' => "File size exceeds {$mb} MB. Please upload a shorter or compressed video.",
            'video.mimes' => 'Please upload an MP4, WebM or MOV video.',
            'video.mimetypes' => 'Please upload an MP4, WebM or MOV video.',
        ];
    }
}
