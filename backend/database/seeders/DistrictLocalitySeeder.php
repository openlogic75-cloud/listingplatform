<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Locality;
use Illuminate\Database\Seeder;

/**
 * First-region reference data: Nagaland, India (Q10 decided).
 *
 * Idempotent and additive: each district/locality is created only if it does
 * not already exist, so re-running after adding names here picks up just the
 * new ones and never touches rows an admin has edited or renamed. Adding new
 * names is a matter of appending to the arrays below and re-running:
 *
 *     php artisan db:seed --class=DistrictLocalitySeeder
 *
 * Kohima, Dimapur, Chümoukedima and Niuland carry their recognised
 * localities (administrative circles, towns and villages, per the district
 * administrations). The remaining districts carry their headquarters until an
 * admin adds more in the dashboard. Everything is editable there.
 */
class DistrictLocalitySeeder extends Seeder
{
    /**
     * District name => locality names.
     *
     * @var array<string, array<int, string>>
     */
    private const REGIONS = [
        'Kohima' => [
            'Kohima', 'Chedema', 'Chedema Model', 'Mezoma', 'Mezo Basa', 'Khonoma',
            'Dzulekema', 'Mengujuma', 'Sechu Zubza', 'Sechüma', 'Jotsoma', 'Thekrejuma',
            'Khonoma Basa', 'Kiruphema Bawe', 'Kiruphema Basa', 'Peducha', 'Dzudza',
            'Phesama', 'Kigwema', 'Mima', 'Pfuchama', 'Jakhama', 'Viswema', 'Khuzama',
            'Kijümetouma', 'Kijümetouma Basa', 'Dihoma', 'Sakhabama', 'Kidima', 'Kezoma',
            'Kezo Basa', 'Mitelephe', 'Nachama', 'Chiechama', 'Rusoma', 'Tsabazou',
            'Thizama', 'Meriema', 'Tsiesema', 'Tsiesema Basa', 'Zhadima', 'Ziezou',
            'Phekerkriema', 'Phekerkriema Basa', 'Viphoma', 'Nerhe Phezha', 'Nerhema',
            'Nerhema Model', 'Botsa', 'Teichuma', 'Seiyhama', 'Seiyha Phesa',
            'Tsiemekhuma', 'Tsiemekhuma Basa', 'Tuophema', 'Tuophe Phezou', 'Gariphema',
            'Gariphema Basa',
        ],
        'Dimapur' => [
            'Dimapur', 'Ao Yimküm', 'Aoyimti', 'Bamunpukhuri', 'Darogajan', 'Darogapathar',
            'Duncan Bosti', 'Ekranipathar', 'Eralibill', 'Indisen', 'Industrial Village Razhüphe',
            'Khusiabill', 'Kuda', 'Lengrijan', 'Naharbari', 'Nuton Bosti', 'Padumpukhuri',
            'Phaipijang', 'Phevima', 'Purana Bazar', 'Rilan', 'Samaguri', 'Sangtamtilla',
            'Senjüm', 'Shozukhü', 'Signal Angami', 'Thahekhü', 'Toluvi', 'Zani',
        ],
        'Mokokchung' => ['Mokokchung'],
        'Mon' => ['Mon'],
        'Phek' => ['Phek'],
        'Tuensang' => ['Tuensang'],
        'Wokha' => ['Wokha'],
        'Zünheboto' => ['Zünheboto'],
        'Longleng' => ['Longleng'],
        'Kiphire' => ['Kiphire'],
        'Peren' => ['Peren'],
        'Chümoukedima' => [
            'Chümoukedima', 'New Chümoukedima', 'Aoyim', 'Bade', 'Chekiye', 'Darogapathar',
            'Diezephe', 'Diphupar', 'Diphupar B', 'Ikishe', 'Khopanala', 'Khriezephe',
            'Kirha', 'Mürise', 'Naga United', 'Seithekema A', 'Seithekema B', 'Seithekema C',
            'Seithekema Old', 'Seluophe', 'Shokhüvi', 'Singrijan', 'Sodzülhou', 'Sovima',
            'Tenyiphe-I', 'Tenyiphe-II', 'Thilixü', 'Toulazouma', 'Tsithrongse', 'Unity',
            'Urra', 'Vidima', 'Virazouma', '5th Mile Model', '7th Mile Model', '7th Mile Village',
            'Tir', 'Bungsang', 'Khaibung', 'Medziphema', 'New Medziphema', 'Molvom', 'Piphema',
            'Pherima', 'Rüzaphema', 'Sirhima', 'Sochünoma', 'Thekrejüma', 'Tsüüma', 'Dhansiripar',
        ],
        'Niuland' => [
            'Niuland', 'Kuhuboto', 'Nihokhu', 'Nihoto', 'Aquqhnaqua', 'Aghunaqa', 'Ahoto',
            'Akito', 'Aoyimchen', 'Ghonivi', 'Ghosito', 'Ghotovi', 'Hakhezhe', 'Henito',
            'Hetoi', 'Heviqhe', 'Hevishe', 'Hevuxu', 'Hezeto', 'Hezulho', 'Homeland',
            'Hovishe', 'Hovukhu', 'Hozheto', 'Hukhai', 'Husto', 'Izhevi', 'Jekishe',
            'Jexuche', 'Khaghaboto', 'Khehuto', 'Khuhoi', 'Khutovi', 'Kikheye', 'Kiyelho',
            'Kuhoxu', 'L.Vihoto', 'Luheje', 'Luhevi', 'Lukuto', 'Mughavi', 'Ngamjalan',
            'Nguvihe', 'Nikihe', 'Nikikhe', 'Nitozu', 'Nizheto', 'P.Vihoto', 'Padala',
            'Phuwoto', 'Pihekhu', 'Pishikhu', 'Qhitohe', 'R.Hovishe', 'Sahoi', 'Shitoi',
            'Shiwoto', 'Shoqhevi', 'Sunito', 'Tohoi', 'Tohokhu', 'Tokishe', 'Vikheto',
            'Vishiyi', 'Viyito', 'Xukhuvi', 'Xukiye', 'Yetoho', 'Yeveto', 'Zaka Station',
            'Zuheshe', 'Zukihe', 'Zutoi',
        ],
        'Tseminyü' => ['Tseminyü'],
        'Shamator' => ['Shamator'],
    ];

    public function run(): void
    {
        $added = 0;

        foreach (self::REGIONS as $districtName => $localityNames) {
            $district = District::query()->firstOrCreate(
                ['name' => $districtName],
                ['is_active' => true],
            );

            foreach ($localityNames as $localityName) {
                $locality = Locality::query()->firstOrCreate(
                    ['district_id' => $district->id, 'name' => $localityName],
                    ['is_active' => true],
                );

                if ($locality->wasRecentlyCreated) {
                    $added++;
                }
            }
        }

        $this->command?->info("Districts and localities ready ({$added} localities added).");
    }
}
