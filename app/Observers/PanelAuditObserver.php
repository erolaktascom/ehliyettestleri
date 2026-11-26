<?php
namespace App\Observers;
use Illuminate\Database\Eloquent\Model;
use App\Support\PanelAudit;
class PanelAuditObserver {
    public function saved(Model $model): void {
        if ($model instanceof \App\Models\SiteSetting || $model instanceof \App\Models\SeoSetting) {
            request()->attributes->remove('_shared_site_settings');
        }
    }
    public function created(Model $model): void { PanelAudit::record($model,'Eklendi'); }
    public function deleted(Model $model): void { PanelAudit::record($model,'Silindi'); }
    public function updated(Model $model): void {
        $fields=array_diff(array_keys($model->getChanges()), ['updated_at','created_at','views_count','view_count',
            'source_model','source_id','question_count','usage_count','last_used_at','request_count','last_seen_at','remember_token','last_login_at',
            'created_by','creator_name','last_edited_by','last_editor_name','content_updated_at','first_published_at','publication_history_unknown']);
        $fields = array_filter($fields, fn ($field) => !(in_array($model->getRawOriginal($field), [null, ''], true)
            && in_array($model->getAttributes()[$field] ?? null, [null, ''], true)));
        if ($fields) PanelAudit::record($model,'Düzenlendi',array_values($fields));
    }
}
