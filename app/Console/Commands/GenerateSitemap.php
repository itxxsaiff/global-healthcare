<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\City;
use App\Models\Condition;
use App\Models\Page;
use App\Models\PractitionerProfile;
use App\Models\Question;
use App\Models\Specialty;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

/** Writes public/sitemap.xml. Scheduled daily; only public, indexable pages. */
class GenerateSitemap extends Command
{
    protected $signature = 'app:sitemap';

    protected $description = 'Generate public/sitemap.xml';

    public function handle(): int
    {
        $map = Sitemap::create()
            ->add(Url::create(route('home'))->setPriority(1.0)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY))
            ->add(Url::create(route('directory'))->setPriority(0.9))
            ->add(Url::create(route('questions.index'))->setPriority(0.7))
            ->add(Url::create(route('articles.index'))->setPriority(0.7))
            ->add(Url::create(route('for-practitioners'))->setPriority(0.5));

        // Specialty pages, and specialty × city only where someone is actually listed.
        $live = PractitionerProfile::query()->public()->get(['id', 'primary_specialty_id', 'city_id', 'slug', 'updated_at']);
        foreach (Specialty::query()->whereNull('parent_id')->where('is_active', true)->get() as $s) {
            $map->add(Url::create(route('specialties.show', $s))->setPriority(0.8));
            foreach ($live->where('primary_specialty_id', $s->id)->pluck('city_id')->filter()->unique() as $cityId) {
                $map->add(Url::create(route('specialties.city', [$s, City::find($cityId)->slug]))->setPriority(0.7));
            }
        }

        Condition::query()->active()->each(fn ($c) => $map->add(Url::create(route('conditions.show', $c))->setPriority(0.7)));
        $live->each(fn ($p) => $map->add(Url::create(route('practitioners.show', $p))->setLastModificationDate($p->updated_at)->setPriority(0.8)));
        Question::query()->published()->each(fn ($q) => $map->add(Url::create(route('questions.show', $q))->setLastModificationDate($q->updated_at)->setPriority(0.6)));
        Article::query()->published()->each(fn ($a) => $map->add(Url::create(route('articles.show', $a))->setLastModificationDate($a->updated_at)->setPriority(0.6)));
        Page::query()->each(fn ($p) => $map->add(Url::create(route('pages.show', $p))->setPriority(0.2)));

        $map->writeToFile(public_path('sitemap.xml'));
        $this->info('Sitemap written with '.count($map->getTags()).' URLs.');

        return self::SUCCESS;
    }
}
