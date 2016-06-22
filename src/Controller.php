<?php namespace camdjn\TranslationManager;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use camdjn\TranslationManager\Models\Translation;
use camdjn\TranslationManager\Models\Group;
use camdjn\TranslationManager\Models\Locale;
use Illuminate\Support\Collection;

class Controller extends BaseController
{
    /** @var \camdjn\TranslationManager\Manager  */
    protected $manager;

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    public function getIndex($group = null)
    {
        if(isset($group)){
            $group = Group::find($group);
            $allTranslations = Translation::where('group_id', $group->id)->orderBy('key', 'asc')->get();
        }else{
            $allTranslations = Translation::where('group_id', $group)->orderBy('key', 'asc')->get();
        }

        $locales = Locale::all();

        $groups = Group::groupBy('label');
        $excludedGroups = $this->manager->getConfig('exclude_groups');
        if($excludedGroups){
            $groups->whereNotIn('label', $excludedGroups);
        }
        $groups = $groups->lists('id', 'label');
        if ($groups instanceof Collection) {
            $groups = $groups->all();
        }

        return view('translation-manager::index')
            ->with('allTranslations', $allTranslations)
            ->with('locales', $locales)
            ->with('groups', $groups)
            ->with('group', $group)
            ->with('editUrl', action('\camdjn\TranslationManager\Controller@postEdit', [$group]))
            ->with('deleteEnabled', $this->manager->getConfig('delete_enabled'));
    }


    public function getView($group)
    {
        return $this->getIndex($group);
    }

    public function postAdd(Request $request, $group)
    {
        $keys = explode("\n", $request->get('keys'));

        foreach($keys as $key){
            $key = trim($key);
            if($group && $key){
                $this->manager->missingKey('*', $group, $key);
            }
        }
        return redirect()->back();
    }

    public function postEdit(Request $request, $group, $locale, $key)
    {

        $g = Group::find($group);
        if(!in_array($g->label, $this->manager->getConfig('exclude_groups'))) {

            $translation = Translation::firstOrNew([
                'locale_id' => $locale,
                'group_id' => $g->id,
                'key' => $key,
            ]);

            $translation->value = (string) $request->get('value') ?: null;
            $translation->status = Translation::STATUS_CHANGED;
            $translation->save();

            return back();
        }
    }

    public function getDelete($group, $key)
    {
        $g = Group::find($group);
        if(!in_array($g->id, $this->manager->getConfig('exclude_groups')) && $this->manager->getConfig('delete_enabled')) {
            Translation::where('group_id', $g->id)->where('key', $key)->delete();
            return back()->with('successDelete', $key);
        }
    }

    public function getImport()
    {
        $counter = $this->manager->importTranslations();
        return back()->with('counter', $counter);
    }

    public function getEmpty($group, $locale, $key)
    {
        $translation = Translation::where('group_id', $group)->where('locale_id', $locale)->where('key', $key)->first();
        $translation->value = null;
        $translation->save();

        return back();
    }


    public function postFind()
    {
        $numFound = $this->manager->findTranslations();

        return back()->with('findCounter', $numFound);
    }

    public function postPublish($group)
    {
        $this->manager->exportTranslations($group);
        return back()->with('successPublish', 'publication done !!!');
    }

}
