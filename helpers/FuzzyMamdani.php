<?php
class FuzzyMamdani {
    

    public static function kurvaTurun($x, $bawah, $tengah) {
        if ($bawah == $tengah) return ($x <= $bawah) ? 1 : 0;
        if ($x <= $bawah) return 1;
        if ($x > $bawah && $x < $tengah) return ($tengah - $x) / ($tengah - $bawah);
        if ($x >= $tengah) return 0;
        return 0;
    }

    public static function kurvaSegitiga($x, $bawah, $tengah, $atas) {
        if ($bawah == $tengah || $tengah == $atas) return 0;
        if ($x <= $bawah || $x >= $atas) return 0;
        if ($x > $bawah && $x <= $tengah) return ($x - $bawah) / ($tengah - $bawah);
        if ($x > $tengah && $x < $atas) return ($atas - $x) / ($atas - $tengah);
        return 0;
    }

    public static function kurvaNaik($x, $tengah, $atas) {
        if ($tengah == $atas) return ($x >= $atas) ? 1 : 0;
        if ($x <= $tengah) return 0;
        if ($x > $tengah && $x < $atas) return ($x - $tengah) / ($atas - $tengah);
        if ($x >= $atas) return 1;
        return 0;
    }


    public static function kurvaLinearTurun($x, $min, $max) {
        if ($min == $max) return ($x <= $min) ? 1 : 0;
        if ($x <= $min) return 1;
        if ($x >= $max) return 0;
        return ($max - $x) / ($max - $min);
    }

    public static function kurvaLinearNaik($x, $min, $max) {
        if ($min == $max) return ($x >= $max) ? 1 : 0;
        if ($x <= $min) return 0;
        if ($x >= $max) return 1;
        return ($x - $min) / ($max - $min);
    }

    public static function getKategoriDominan($nilai) {
        $maxKey = '';
        $maxVal = -1;
        foreach($nilai as $kategori => $derajat) {
            if ($derajat > $maxVal) {
                $maxVal = $derajat;
                $maxKey = $kategori;
            }
        }
        return $maxKey;
    }

    public static function fuzzifikasi($item) {
        $stok    = (float)$item['stock'];
        $demand  = (float)$item['demand'];
        

        $leadTime = isset($item['lead_time_days']) ? (float)$item['lead_time_days'] : 0;
        
        $harga   = (float)$item['price'];
        $expired = (float)$item['expired_days'];


        $sBawah  = (float)$item['fuzzy_stok_bawah'];
        $sTengah = (float)$item['fuzzy_stok_tengah'];
        $sAtas   = (float)$item['fuzzy_stok_atas'];
        $uStok = [
            'Sedikit' => self::kurvaTurun($stok, $sBawah, $sTengah),
            'Sedang'  => self::kurvaSegitiga($stok, $sBawah, $sTengah, $sAtas),
            'Banyak'  => self::kurvaNaik($stok, $sTengah, $sAtas)
        ];


        $dBawah  = (float)$item['fuzzy_demand_bawah'];
        $dTengah = (float)$item['fuzzy_demand_tengah'];
        $dAtas   = (float)$item['fuzzy_demand_atas'];
        $uDemand = [
            'Rendah' => self::kurvaTurun($demand, $dBawah, $dTengah),
            'Normal' => self::kurvaSegitiga($demand, $dBawah, $dTengah, $dAtas),
            'Tinggi' => self::kurvaNaik($demand, $dTengah, $dAtas)
        ];


        $lBawah  = (float)$item['fuzzy_leadtime_bawah'];
        $lTengah = (float)$item['fuzzy_leadtime_tengah'];
        $lAtas   = (float)$item['fuzzy_leadtime_atas'];
        $uLeadTime = [
            'Cepat'  => self::kurvaTurun($leadTime, $lBawah, $lTengah),
            'Sedang' => self::kurvaSegitiga($leadTime, $lBawah, $lTengah, $lAtas),
            'Lama'   => self::kurvaNaik($leadTime, $lTengah, $lAtas)
        ];


        $hBawah = (float)$item['fuzzy_harga_bawah'];
        $hAtas  = (float)$item['fuzzy_harga_atas'];
        $uHarga = [
            'Murah' => self::kurvaLinearTurun($harga, $hBawah, $hAtas),
            'Mahal' => self::kurvaLinearNaik($harga, $hBawah, $hAtas)
        ];


        $eBawah = (float)$item['fuzzy_expired_bawah'];
        $eAtas  = (float)$item['fuzzy_expired_atas'];
        $uExpired = [
            'Dekat' => self::kurvaLinearTurun($expired, $eBawah, $eAtas),
            'Lama'  => self::kurvaLinearNaik($expired, $eBawah, $eAtas)
        ];

        return [
            'Stok'     => $uStok,
            'Demand'   => $uDemand,
            'LeadTime' => $uLeadTime,
            'Harga'    => $uHarga,
            'Expired'  => $uExpired
        ];
    }

    public static function evaluasiAturan($fuzzifikasi) {
        $uS = $fuzzifikasi['Stok'];
        $uD = $fuzzifikasi['Demand'];
        $uL = $fuzzifikasi['LeadTime'];
        $uH = $fuzzifikasi['Harga'];
        $uE = $fuzzifikasi['Expired'];

        $maxOutput = [
            'Tidak_Beli'    => 0,
            'Sedikit'       => 0,
            'Sedang'        => 0,
            'Banyak'        => 0,
            'Sangat_Banyak' => 0
        ];

        $rulesData = self::getRules();

        foreach($rulesData as $r) {

            $val = min($uS[$r[0]], $uD[$r[1]], $uL[$r[2]], $uH[$r[3]], $uE[$r[4]]);
            $out = $r[5];
            

            if ($val > $maxOutput[$out]) {
                $maxOutput[$out] = $val;
            }
        }

        return $maxOutput;
    }

    public static function defuzzifikasi($maxOutput, $stokMax) {

        $titikZ = [
            'Tidak_Beli'    => 0,
            'Sedikit'       => $stokMax * 0.25,
            'Sedang'        => $stokMax * 0.50,
            'Banyak'        => $stokMax * 0.75,
            'Sangat_Banyak' => $stokMax * 1.00
        ];

        $pembilang = 0;
        $penyebut = 0;

        foreach($maxOutput as $kategori => $nilaiMax) {
            $pembilang += ($nilaiMax * $titikZ[$kategori]);
            $penyebut  += $nilaiMax;
        }

        if ($penyebut == 0) return 0;
        
        return $pembilang / $penyebut;
    }

    public static function hitungPengadaan($item) {

        $fuzzifikasi = self::fuzzifikasi($item);
        $komposisi   = self::evaluasiAturan($fuzzifikasi);
        
        $stokMax = isset($item['stok_max']) ? (float)$item['stok_max'] : (float)$item['fuzzy_stok_atas'];
        $stok_sekarang = (float)$item['stock'];
        

        $z = self::defuzzifikasi($komposisi, $stokMax);
        

        if (($stok_sekarang + $z) > $stokMax) {
            $z = $stokMax - $stok_sekarang;
        }


        if ($z < 0) {
            $z = 0;
        }
        
        return [
            'fuzzifikasi' => $fuzzifikasi,
            'komposisi'   => $komposisi,
            'z'           => round($z, 2)
        ];
    }

    public static function getStatusPrioritas($z, $stokMax) {
        $persentase = ($stokMax > 0) ? ($z / $stokMax) : 0;
        
        if ($persentase >= 0.75) {
            return "Prioritas Utama"; 
        } elseif ($persentase > 0 && $persentase < 0.75) {
            return "Prioritas Cadangan"; 
        } else {
            return "Aman"; 
        }
    }


    private static function getRules() {
        return [
            ['Sedikit', 'Tinggi', 'Cepat', 'Murah', 'Lama', 'Sangat_Banyak'],
            ['Sedikit', 'Tinggi', 'Cepat', 'Murah', 'Dekat', 'Banyak'],
            ['Sedikit', 'Tinggi', 'Cepat', 'Mahal', 'Lama', 'Banyak'],
            ['Sedikit', 'Tinggi', 'Cepat', 'Mahal', 'Dekat', 'Sedang'],
            ['Sedikit', 'Tinggi', 'Sedang', 'Murah', 'Lama', 'Banyak'],
            ['Sedikit', 'Tinggi', 'Sedang', 'Murah', 'Dekat', 'Sedang'],
            ['Sedikit', 'Tinggi', 'Sedang', 'Mahal', 'Lama', 'Sedang'],
            ['Sedikit', 'Tinggi', 'Sedang', 'Mahal', 'Dekat', 'Sedikit'],
            ['Sedikit', 'Tinggi', 'Lama', 'Murah', 'Lama', 'Sedang'],
            ['Sedikit', 'Tinggi', 'Lama', 'Murah', 'Dekat', 'Sedikit'],
            ['Sedikit', 'Tinggi', 'Lama', 'Mahal', 'Lama', 'Sedikit'],
            ['Sedikit', 'Tinggi', 'Lama', 'Mahal', 'Dekat', 'Sedikit'],
            ['Sedikit', 'Normal', 'Cepat', 'Murah', 'Lama', 'Banyak'],
            ['Sedikit', 'Normal', 'Cepat', 'Murah', 'Dekat', 'Sedang'],
            ['Sedikit', 'Normal', 'Cepat', 'Mahal', 'Lama', 'Sedang'],
            ['Sedikit', 'Normal', 'Cepat', 'Mahal', 'Dekat', 'Sedikit'],
            ['Sedikit', 'Normal', 'Sedang', 'Murah', 'Lama', 'Sedang'],
            ['Sedikit', 'Normal', 'Sedang', 'Murah', 'Dekat', 'Sedikit'],
            ['Sedikit', 'Normal', 'Sedang', 'Mahal', 'Lama', 'Sedikit'],
            ['Sedikit', 'Normal', 'Sedang', 'Mahal', 'Dekat', 'Sedikit'],
            ['Sedikit', 'Normal', 'Lama', 'Murah', 'Lama', 'Sedikit'],
            ['Sedikit', 'Normal', 'Lama', 'Murah', 'Dekat', 'Sedikit'],
            ['Sedikit', 'Normal', 'Lama', 'Mahal', 'Lama', 'Sedikit'],
            ['Sedikit', 'Normal', 'Lama', 'Mahal', 'Dekat', 'Tidak_Beli'],
            ['Sedikit', 'Rendah', 'Cepat', 'Murah', 'Lama', 'Sedang'],
            ['Sedikit', 'Rendah', 'Cepat', 'Murah', 'Dekat', 'Sedikit'],
            ['Sedikit', 'Rendah', 'Cepat', 'Mahal', 'Lama', 'Sedikit'],
            ['Sedikit', 'Rendah', 'Cepat', 'Mahal', 'Dekat', 'Tidak_Beli'],
            ['Sedikit', 'Rendah', 'Sedang', 'Murah', 'Lama', 'Sedikit'],
            ['Sedikit', 'Rendah', 'Sedang', 'Murah', 'Dekat', 'Tidak_Beli'],
            ['Sedikit', 'Rendah', 'Sedang', 'Mahal', 'Lama', 'Tidak_Beli'],
            ['Sedikit', 'Rendah', 'Sedang', 'Mahal', 'Dekat', 'Tidak_Beli'],
            ['Sedikit', 'Rendah', 'Lama', 'Murah', 'Lama', 'Tidak_Beli'],
            ['Sedikit', 'Rendah', 'Lama', 'Murah', 'Dekat', 'Tidak_Beli'],
            ['Sedikit', 'Rendah', 'Lama', 'Mahal', 'Lama', 'Tidak_Beli'],
            ['Sedikit', 'Rendah', 'Lama', 'Mahal', 'Dekat', 'Tidak_Beli'],
            
            ['Sedang', 'Tinggi', 'Cepat', 'Murah', 'Lama', 'Sangat_Banyak'],
            ['Sedang', 'Tinggi', 'Cepat', 'Murah', 'Dekat', 'Banyak'],
            ['Sedang', 'Tinggi', 'Cepat', 'Mahal', 'Lama', 'Banyak'],
            ['Sedang', 'Tinggi', 'Cepat', 'Mahal', 'Dekat', 'Sedang'],
            ['Sedang', 'Tinggi', 'Sedang', 'Murah', 'Lama', 'Banyak'],
            ['Sedang', 'Tinggi', 'Sedang', 'Murah', 'Dekat', 'Sedang'],
            ['Sedang', 'Tinggi', 'Sedang', 'Mahal', 'Lama', 'Sedang'],
            ['Sedang', 'Tinggi', 'Sedang', 'Mahal', 'Dekat', 'Sedikit'],
            ['Sedang', 'Tinggi', 'Lama', 'Murah', 'Lama', 'Sedang'],
            ['Sedang', 'Tinggi', 'Lama', 'Murah', 'Dekat', 'Sedikit'],
            ['Sedang', 'Tinggi', 'Lama', 'Mahal', 'Lama', 'Sedikit'],
            ['Sedang', 'Tinggi', 'Lama', 'Mahal', 'Dekat', 'Tidak_Beli'],
            ['Sedang', 'Normal', 'Cepat', 'Murah', 'Lama', 'Banyak'],
            ['Sedang', 'Normal', 'Cepat', 'Murah', 'Dekat', 'Sedang'],
            ['Sedang', 'Normal', 'Cepat', 'Mahal', 'Lama', 'Sedang'],
            ['Sedang', 'Normal', 'Cepat', 'Mahal', 'Dekat', 'Sedikit'],
            ['Sedang', 'Normal', 'Sedang', 'Murah', 'Lama', 'Sedang'],
            ['Sedang', 'Normal', 'Sedang', 'Murah', 'Dekat', 'Sedikit'],
            ['Sedang', 'Normal', 'Sedang', 'Mahal', 'Lama', 'Sedikit'],
            ['Sedang', 'Normal', 'Sedang', 'Mahal', 'Dekat', 'Tidak_Beli'],
            ['Sedang', 'Normal', 'Lama', 'Murah', 'Lama', 'Sedikit'],
            ['Sedang', 'Normal', 'Lama', 'Murah', 'Dekat', 'Tidak_Beli'],
            ['Sedang', 'Normal', 'Lama', 'Mahal', 'Lama', 'Tidak_Beli'],
            ['Sedang', 'Normal', 'Lama', 'Mahal', 'Dekat', 'Tidak_Beli'],
            ['Sedang', 'Rendah', 'Cepat', 'Murah', 'Lama', 'Sedang'],
            ['Sedang', 'Rendah', 'Cepat', 'Murah', 'Dekat', 'Sedikit'],
            ['Sedang', 'Rendah', 'Cepat', 'Mahal', 'Lama', 'Sedikit'],
            ['Sedang', 'Rendah', 'Cepat', 'Mahal', 'Dekat', 'Tidak_Beli'],
            ['Sedang', 'Rendah', 'Sedang', 'Murah', 'Lama', 'Sedikit'],
            ['Sedang', 'Rendah', 'Sedang', 'Murah', 'Dekat', 'Tidak_Beli'],
            ['Sedang', 'Rendah', 'Sedang', 'Mahal', 'Lama', 'Tidak_Beli'],
            ['Sedang', 'Rendah', 'Sedang', 'Mahal', 'Dekat', 'Tidak_Beli'],
            ['Sedang', 'Rendah', 'Lama', 'Murah', 'Lama', 'Tidak_Beli'],
            ['Sedang', 'Rendah', 'Lama', 'Murah', 'Dekat', 'Tidak_Beli'],
            ['Sedang', 'Rendah', 'Lama', 'Mahal', 'Lama', 'Tidak_Beli'],
            ['Sedang', 'Rendah', 'Lama', 'Mahal', 'Dekat', 'Tidak_Beli'],

            ['Banyak', 'Tinggi', 'Cepat', 'Murah', 'Lama', 'Banyak'],
            ['Banyak', 'Tinggi', 'Cepat', 'Murah', 'Dekat', 'Sedang'],
            ['Banyak', 'Tinggi', 'Cepat', 'Mahal', 'Lama', 'Sedang'],
            ['Banyak', 'Tinggi', 'Cepat', 'Mahal', 'Dekat', 'Sedikit'],
            ['Banyak', 'Tinggi', 'Sedang', 'Murah', 'Lama', 'Sedang'],
            ['Banyak', 'Tinggi', 'Sedang', 'Murah', 'Dekat', 'Sedikit'],
            ['Banyak', 'Tinggi', 'Sedang', 'Mahal', 'Lama', 'Sedikit'],
            ['Banyak', 'Tinggi', 'Sedang', 'Mahal', 'Dekat', 'Tidak_Beli'],
            ['Banyak', 'Tinggi', 'Lama', 'Murah', 'Lama', 'Sedikit'],
            ['Banyak', 'Tinggi', 'Lama', 'Murah', 'Dekat', 'Tidak_Beli'],
            ['Banyak', 'Tinggi', 'Lama', 'Mahal', 'Lama', 'Tidak_Beli'],
            ['Banyak', 'Tinggi', 'Lama', 'Mahal', 'Dekat', 'Tidak_Beli'],
            ['Banyak', 'Normal', 'Cepat', 'Murah', 'Lama', 'Sedang'],
            ['Banyak', 'Normal', 'Cepat', 'Murah', 'Dekat', 'Sedikit'],
            ['Banyak', 'Normal', 'Cepat', 'Mahal', 'Lama', 'Sedikit'],
            ['Banyak', 'Normal', 'Cepat', 'Mahal', 'Dekat', 'Tidak_Beli'],
            ['Banyak', 'Normal', 'Sedang', 'Murah', 'Lama', 'Sedikit'],
            ['Banyak', 'Normal', 'Sedang', 'Murah', 'Dekat', 'Tidak_Beli'],
            ['Banyak', 'Normal', 'Sedang', 'Mahal', 'Lama', 'Tidak_Beli'],
            ['Banyak', 'Normal', 'Sedang', 'Mahal', 'Dekat', 'Tidak_Beli'],
            ['Banyak', 'Normal', 'Lama', 'Murah', 'Lama', 'Tidak_Beli'],
            ['Banyak', 'Normal', 'Lama', 'Murah', 'Dekat', 'Tidak_Beli'],
            ['Banyak', 'Normal', 'Lama', 'Mahal', 'Lama', 'Tidak_Beli'],
            ['Banyak', 'Normal', 'Lama', 'Mahal', 'Dekat', 'Tidak_Beli'],
            ['Banyak', 'Rendah', 'Cepat', 'Murah', 'Lama', 'Sedikit'],
            ['Banyak', 'Rendah', 'Cepat', 'Murah', 'Dekat', 'Tidak_Beli'],
            ['Banyak', 'Rendah', 'Cepat', 'Mahal', 'Lama', 'Tidak_Beli'],
            ['Banyak', 'Rendah', 'Cepat', 'Mahal', 'Dekat', 'Tidak_Beli'],
            ['Banyak', 'Rendah', 'Sedang', 'Murah', 'Lama', 'Tidak_Beli'],
            ['Banyak', 'Rendah', 'Sedang', 'Murah', 'Dekat', 'Tidak_Beli'],
            ['Banyak', 'Rendah', 'Sedang', 'Mahal', 'Lama', 'Tidak_Beli'],
            ['Banyak', 'Rendah', 'Sedang', 'Mahal', 'Dekat', 'Tidak_Beli'],
            ['Banyak', 'Rendah', 'Lama', 'Murah', 'Lama', 'Tidak_Beli'],
            ['Banyak', 'Rendah', 'Lama', 'Murah', 'Dekat', 'Tidak_Beli'],
            ['Banyak', 'Rendah', 'Lama', 'Mahal', 'Lama', 'Tidak_Beli'],
            ['Banyak', 'Rendah', 'Lama', 'Mahal', 'Dekat', 'Tidak_Beli']
        ];
    }
}
?>