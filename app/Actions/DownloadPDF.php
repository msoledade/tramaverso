<?php

namespace App\Actions;

use Statamic\Actions\Action;
use Statamic\Contracts\Entries\Entry;
use Carbon\Carbon;
use Statamic\Auth\User;
use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Fields\Value;
use Statamic\Fields\LabeledValue;
use Barryvdh\DomPDF\Facade\Pdf;

class DownloadPDF extends Action
{

    public static function title()
    {
        return __("Download PDF");
    }

    public function visibleTo($item)
    {
        return ($item instanceof Entry &&
            isset($item->collection) &&
            isset($item->collection->handle)
        );
    }

    public function authorize($user, $item)
    {
        return $user->can('edit', $item);
    }

    /**
     * The run method
     *
     * @return mixed
     */
    public function run($items, $values)
    {
        $firstEntry = $items->first();
        $entryFields = $firstEntry->blueprint()
            ->tabs()
            ->flatMap(fn($section) => $section->fields()->all());
        $headings = $entryFields->values();
        $entries = $items->map(function ($entry) use ($headings) {
            return $headings->mapWithKeys(function ($heading) use ($entry) {
                $value = $entry->augmentedValue($heading->handle());
                return [
                    $heading->handle() =>
                    [
                        'name' => $heading->display(),
                        'value' => $this->toString($value)
                    ]
                ];
            });
        });
        $pdf = Pdf::loadView(__DIR__ . '/../resources/views/pdf/pdf.antlers.html', [
            'collection' => $firstEntry->collection,
            'entries' => $entries,
        ])->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);
        return $pdf->download('export_' . $firstEntry->collection->handle . '_' . date('Y_m_d_H:i') . '.pdf');
    }

    private function toString($value)
    {
        if ($value instanceof Value) {
            $value = $value->value();
        }

        if ($value instanceof Carbon) {
            return $value->format('d-m-Y H:i');
        }

        if ($value instanceof Entry) {
            return $value->get('title');
        }

        if ($value instanceof User) {
            return $value->name();
        }

        if ($value instanceof Term) {
            return $value->title();
        }

        if ($value instanceof LabeledValue) {
            return $value->label();
        }

        if ($value instanceof Asset) {
            return '<img width="100%" src="' . url($value->url()) . '">';
        }

        if (empty($value)) {
            return null;
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
