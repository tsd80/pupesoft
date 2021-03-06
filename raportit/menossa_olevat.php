<?php

//* T�m� skripti k�ytt�� slave-tietokantapalvelinta *//
$useslave = 1;

if (isset($_POST["supertee"])) {
  if ($_POST["supertee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

require "../inc/parametrit.inc";

if (isset($supertee)) {
  if ($supertee == "lataa_tiedosto") {
    readfile("/tmp/".$tmpfilenimi);
    exit;
  }
}

echo "<font class='head'>".t("Menossa olevat tilaukset")."</font><hr>";

if ($tee == 'NAYTATILAUS') {
  echo "<font class='head'>Tilausnro: $tunnus</font><hr>";
  require "naytatilaus.inc";
  echo "<br><br><br>";
  $tee = "";
}

if (!isset($vain_toimittamattomat_kaikki)) $vain_toimittamattomat_kaikki = '';
if (!isset($vain_toimittamattomat)) $vain_toimittamattomat = '';

if ($ytunnus != '' and $ytunnus != 'TULKAIKKI') {
  require "inc/asiakashaku.inc";
}

if ($ytunnus != '' or $ytunnus == 'TULKAIKKI') {

  $tilat = "and lasku.tila in ('L', 'N') and lasku.alatila != 'X'";

  if ($ytunnus != 'TULKAIKKI') {
    $lisa = " and lasku.ytunnus = '{$ytunnus}' ";

    if (!empty($vain_toimittamattomat)) {
      $tilat = "and (lasku.tila = 'N' or (lasku.tila = 'L' and lasku.alatila IN ('A','B','BD','C')))";
    }
  }
  else {
    $lisa = " ";

    if (!empty($vain_toimittamattomat_kaikki)) {
      $tilat = "and (lasku.tila = 'N' or (lasku.tila = 'L' and lasku.alatila IN ('A','B','BD','C')))";
    }
  }

  if ($suunta == '' or $suunta == "DESC") {
    $suunta = "ASC";
  }
  else {
    $suunta = "DESC";
  }

  echo "<table><tr>";
  echo "<th>".t("tilno")."</th>";
  echo "<th>".t("ytunnus")."</th>";
  echo "<th>".t("nimi")."</th>";
  echo "<th><a href='?suunta=$suunta&ytunnus=$ytunnus'>".t("Toimitusaika")."</a></th>";
  echo "<th>".t("rivim��r�")."</th>";
  echo "<th>".t("kplm��r�")."</th>";
  echo "<th>".t("arvo")."</th>";
  echo "<th>".t("valuutta")."</th>";
  echo "</tr>";

  $query_ale_lisa = generoi_alekentta('M');

  $query = "SELECT lasku.tunnus, lasku.nimi, lasku.toimaika, lasku.valkoodi, lasku.ytunnus,
            count(*) maara,
            sum(tilausrivi.varattu+tilausrivi.jt) tilattu,
            round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
            round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) jt_arvo
            FROM lasku
            JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D')
            WHERE lasku.yhtio = '$kukarow[yhtio]'
            {$tilat}
            $lisa
            GROUP BY 1,2,3,4,5
            ORDER BY lasku.toimaika $suunta, lasku.nimi, lasku.tunnus";
  $result = pupe_query($query);

  if ($vain_excel != '' or $vain_excel_kaikki != '') {
    include 'inc/pupeExcel.inc';

    $worksheet    = new pupeExcel();
    $format_bold = array("bold" => TRUE);

    $excelrivi = 0;

    if (isset($worksheet)) {
      $excelsarake = 0;

      $worksheet->write($excelrivi, $excelsarake, t("Tilno"), $format_bold);
      $excelsarake++;
      $worksheet->write($excelrivi, $excelsarake, t("Ytunnus"), $format_bold);
      $excelsarake++;
      $worksheet->write($excelrivi, $excelsarake, t("Nimi"), $format_bold);
      $excelsarake++;
      $worksheet->write($excelrivi, $excelsarake, t("Toimitusaika"), $format_bold);
      $excelsarake++;
      $worksheet->write($excelrivi, $excelsarake, t("rivim��r�"), $format_bold);
      $excelsarake++;
      $worksheet->write($excelrivi, $excelsarake, t("kplm��r�"), $format_bold);
      $excelsarake++;
      $worksheet->write($excelrivi, $excelsarake, t("arvo"), $format_bold);
      $excelsarake++;
      $worksheet->write($excelrivi, $excelsarake, t("valuutta"), $format_bold);

      $excelsarake = 0;
      $excelrivi++;
    }
  }

  $rivsum = 0;
  $tilsum = 0;
  $eursum = 0;

  while ($tulrow = mysql_fetch_array($result)) {
    echo "<tr>";
    echo "<td><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$tulrow[tunnus]&ytunnus=$ytunnus&suunta=$suunta'>$tulrow[tunnus]</a></td>";
    echo "<td>$tulrow[ytunnus]</td>";
    echo "<td>$tulrow[nimi]</td>";
    echo "<td>".tv1dateconv($tulrow["toimaika"])."</td>";
    echo "<td align='right'>".sprintf("%.0f", $tulrow["maara"])."</td>";
    echo "<td align='right'>".sprintf("%.2f", $tulrow["tilattu"])."</td>";
    echo "<td align='right'>".sprintf("%.2f", $tulrow["arvo"])."</td>";
    echo "<td>$tulrow[valkoodi]</td>";
    echo "</tr>";

    $rivsum += $tulrow["maara"];
    $tilsum += $tulrow["tilattu"];
    $eursum += $tulrow["arvo"];

    if (isset($worksheet)) {
      $excelsarake = 0;

      $worksheet->writeString($excelrivi, $excelsarake, $tulrow["tunnus"]);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $tulrow["ytunnus"]);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $tulrow["nimi"]);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, tv1dateconv($tulrow["toimaika"]));
      $excelsarake++;
      $worksheet->writeNumber($excelrivi, $excelsarake, $tulrow["maara"]);
      $excelsarake++;
      $worksheet->writeNumber($excelrivi, $excelsarake, $tulrow["tilattu"]);
      $excelsarake++;
      $worksheet->writeNumber($excelrivi, $excelsarake, $tulrow["arvo"]);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $tulrow["valkoodi"]);

      $excelsarake = 0;
      $excelrivi++;
    }
  }

  echo "<tr>";
  echo "<th colspan='4'>".t("Yhteens�").":</th>";
  echo "<td class='tumma' align='right'>$rivsum</td>";
  echo "<td class='tumma' align='right'>$tilsum</td>";
  echo "<td class='tumma' align='right'>$eursum</td>";
  echo "<th></th>";
  echo "</tr>";
  echo "</table>";

  if (isset($worksheet)) {
    $excelsarake = 0;

    $excelsarake++;
    $excelsarake++;
    $excelsarake++;
    $excelsarake++;
    $worksheet->writeNumber($excelrivi, $excelsarake, $rivsum, $format_bold);
    $excelsarake++;
    $worksheet->writeNumber($excelrivi, $excelsarake, $tilsum, $format_bold);
    $excelsarake++;
    $worksheet->writeNumber($excelrivi, $excelsarake, $eursum, $format_bold);
    $excelrivi++;
  }

  if (isset($worksheet)) {

    // We need to explicitly close the workbook
    $excelnimi = $worksheet->close();

    echo "<br><table>";
    echo "<tr><th>".t("Tallenna tulos").":</th>";
    echo "<form method='post' class='multisubmit'>";
    echo "<input type='hidden' name='supertee' value='lataa_tiedosto'>";
    echo "<input type='hidden' name='kaunisnimi' value='Menossa_olevat.xlsx'>";
    echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
    echo "<td valign='top' class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
    echo "</table><br>";
  }

  $ytunnus = '';
}

$vain_excelchk = "";
if ($vain_excel != '') {
  $vain_excelchk = "CHECKED";
}

$vain_excelchk_kaikki = "";
if ($vain_excel_kaikki != '') {
  $vain_excelchk_kaikki = "CHECKED";
}

$_chk = !empty($vain_toimittamattomat) ? 'checked' : '';

echo "<br>";
echo "<form name='asiakas' method='post' autocomplete='off'>";
echo "<table>";
echo "<tr>";
echo "<th>".t("Anna ytunnus tai osa nimest�")."</th>";
echo "<td><input type='text' name='ytunnus' value='{$ytunnus}'></td>";
echo "</tr>";
echo "<tr>";
echo "<th>".t("Vain toimittamattomat")."</th>";
echo "<td><input type='checkbox' name='vain_toimittamattomat' {$_chk}></td>";
echo "</tr>";
echo "<tr>";
echo "<th>".t("Raportti Exceliin")."</th>";
echo "<td><input type='checkbox' name='vain_excel' {$vain_excelchk}></td>";
echo "</tr>";
echo "<tr>";
echo "<td class='back' colspan='2'><input type='submit' class='hae_btn' value='".t("Hae")."'></td>";
echo "</tr>";
echo "</table>";
echo "</form>";

echo "<br><br>";

$_chk = !empty($vain_toimittamattomat_kaikki) ? 'checked' : '';

echo "<form name='asiakas' method='post' autocomplete='off'>";
echo "<input type='hidden' name='ytunnus' value='TULKAIKKI'>";
echo "<table>";
echo "<tr><th colspan='2'>".t("Listaa kaikki menossa olevat")."</th></tr>";
echo "<tr>";
echo "<th>".t("Vain toimittamattomat")."</th>";
echo "<td><input type='checkbox' name='vain_toimittamattomat_kaikki' {$_chk}></td>";
echo "</tr>";
echo "<tr>";
echo "<th>".t("Raportti Exceliin")."</th>";
echo "<td><input type='checkbox' name='vain_excel_kaikki' $vain_excelchk_kaikki></td>";
echo "</tr>";
echo "<tr><td class='back' colspan='2'><input type='submit' value='".t("Listaa")."'></td></tr>";
echo "</table>";
echo "</form>";



// kursorinohjausta
$formi  = "asiakas";
$kentta = "ytunnus";

require "../inc/footer.inc";
