<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ServiceApiController extends Controller
{
    const BENEFITS = 'https://run.mocky.io/v3/399b4ce1-5f6e-4983-a9e8-e3fa39e1ea71';
    const FILTERS = 'https://run.mocky.io/v3/06b8dd68-7d6d-4857-85ff-b58e204acbf4';
    const RECORDS = 'https://run.mocky.io/v3/c7a4777f-e383-4122-8a89-70f29a6830c0';

    public function index()
    {

        $benefits = $this->externalService(self::BENEFITS);
        $filters = $this->externalService(self::FILTERS);
        $records = $this->externalService(self::RECORDS);

        $relatedBenefits = $this->combineBenefitsWithRecords($benefits, $filters, $records);
        $groupedBenefits = $this->groupBenefitPerYear($relatedBenefits);

        return response()->json([
            'code'      => 200,
            'success'   => true,
            'data'      => $groupedBenefits,
        ]);
    }

    public function externalService($url) {

        $response = Http::get($url);
        $data = $response->json();

        if ($response->successful() && isset($data['data']) && $data['success']) {
            return collect($data['data']);
        } else {
            return false;
        }
    }

    function filterBenefits(Collection $benefits, Collection $filters) {
        return $benefits->filter(function ($benefit) use ($filters) {
            $filter = $filters->where('id_programa', $benefit['id_programa'])->first();
            if ($filter) {
                return $benefit['monto'] >= $filter['min'] && $benefit['monto'] <= $filter['max'];
            }
            return false;
        });
    }

    function combineBenefitsWithRecords(Collection $benefits, Collection $filters, Collection $records) {
        $filterBenefits = $this->filterBenefits($benefits, $filters);

        return $filterBenefits->map(function ($benefit) use ($records) {
            $record = $records->where('id_programa', $benefit['id_programa'])->first();
            if ($record) {
                $benefit['ficha'] = $record;
            }
            return $benefit;
        });
    }

    function groupBenefitPerYear(Collection $benefits) {
        $grouped = $benefits->groupBy(function ($benefit) {
            return date('Y', strtotime($benefit['fecha']));
        })->map(function ($group, $year) {
            return [
                'year' => $year,
                'num' => $group->count(),
                'beneficios' => $group->map(function ($benefit) use ($year) {
                    return [
                        'id_programa' => $benefit['id_programa'],
                        'monto' => $benefit['monto'],
                        'fecha_recepcion' => $benefit['fecha_recepcion'],
                        'fecha' => $benefit['fecha'],
                        'ano' => "$year",
                        'view' => true,
                        'ficha' => $benefit['ficha'],
                    ];
                })
            ];
        })->sortByDesc('year');

        return $grouped->values();
    }


}
