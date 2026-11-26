<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\{SeoSetting, SeoRouteSetting, Page, SiteSetting};
use App\Support\{SeoTemplates, SeoPageCatalog};

return new class extends Migration {
    public function up(): void
    {
        DB::transaction(function () {
            $setting = SeoSetting::first();
            if (!$setting) {
                DB::table('pages')->where('seo_index', 'inherit')->update(['seo_index'=>'index']);
                DB::table('seo_route_settings')->whereIn('route_key', array_keys(SeoPageCatalog::PAGES))->where('seo_index','inherit')->update(['seo_index'=>'index']);
                return;
            }
            $rules = $setting->content_rules ?? [];
            // Materialize any old standalone-page rules before removing the general controls.
            foreach (Page::all() as $page) $this->preserve($page, $rules['pages'] ?? [], '');
            foreach (SeoPageCatalog::PAGES as $key => $label) {
                $record = SeoRouteSetting::firstOrNew(['route_key' => $key]);
                $this->preserve($record, $rules['static_pages'] ?? [], $key);
            }
            $home = SeoRouteSetting::firstOrNew(['route_key' => 'home']);
            if (trim((string)$home->seo_description) === '' && trim((string)$setting->meta_description) !== '') {
                $home->seo_description = $setting->meta_description;
                $this->persist($home);
            }
            foreach (SeoTemplates::DEFAULT_TITLES as $type => $title) {
                if (trim((string)($rules[$type]['title'] ?? '')) === '') $rules[$type]['title'] = $title;
            }
            DB::table('seo_settings')->where('id', $setting->id)->update([
                'content_rules' => json_encode(SeoTemplates::values($rules), JSON_UNESCAPED_UNICODE),
                'canonical_base' => null, 'meta_description' => null,
            ]);
        });
    }
    private function preserve($record, array $rule, string $key): void
    {
        $context = SeoTemplates::context($record instanceof Page ? $record : null, $key, ['siteSetting' => SiteSetting::first()], '');
        foreach (['title', 'description'] as $field) {
            $attribute = 'seo_'.$field;
            if (trim((string)$record->$attribute) === '' && trim((string)($rule[$field] ?? '')) !== '') {
                $record->$attribute = SeoTemplates::expand($rule[$field], $context);
            }
        }
        if (!in_array($record->seo_index, ['index','noindex'], true)) $record->seo_index = ($rule['index'] ?? '') === 'noindex' ? 'noindex' : 'index';
        if ($record->isDirty()) $this->persist($record);
    }
    private function persist($record): void
    {
        if ($record->exists) DB::table($record->getTable())->where('id',$record->id)->update($record->getDirty());
        else $record->saveQuietly();
    }
    public function down(): void
    {
        throw new RuntimeException('Restore the pre-migration backup to recover removed global SEO rules.');
    }
};