<?php

	if (isset($_POST["tee"])) {
		if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	ini_set('zlib.output_compression', 0);

	require("../inc/parametrit.inc");

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}
	else {

		echo "	<script type='text/javascript'>

					$(function() {

						$('#ruksaa_kaikki').on('click', function() {

							if ($(this).is(':checked')) $('.valitut_checkboxit').attr('checked', true);
							else $('.valitut_checkboxit').attr('checked', false);

						});
					});

				</script>";

		echo "<font class='head'>",t("Ostoehdotus kuukausittain"),"</font><hr>";

		$org_rajaus = $abcrajaus;
		list($abcrajaus,$abcrajaustapa) = explode("##",$abcrajaus);

		if (!isset($abcrajaustapa)) $abcrajaustapa = "TK";
		if (!isset($rappari)) $rappari = "";

		list($ryhmanimet, $ryhmaprossat, , , , ) = hae_ryhmanimet($abcrajaustapa);

		// Jos jt-rivit varaavat saldoa niin se vaikuttaa asioihin
		if ($yhtiorow["varaako_jt_saldoa"] != "") {
			$lisavarattu = " + tilausrivi.varattu";
		}
		else {
			$lisavarattu = "";
		}

		// Tarvittavat p�iv�m��r�t
		for ($i = 1; $i < 13; $i++) {
			if (!isset(${"kka{$i}"})) ${"kka{$i}"} = date("m", mktime(0, 0, 0, date("m")-$i+1, 1, date("Y")));
			if (!isset(${"ppa{$i}"})) ${"ppa{$i}"} = date("d", mktime(0, 0, 0, date("m"), 1, date("Y")));
			if (!isset(${"vva{$i}"})) ${"vva{$i}"} = date("Y", mktime(0, 0, 0, date("m")-$i+1, date("d"), date("Y")));

			if (!isset(${"kkl{$i}"})) ${"kkl{$i}"} = date("m", mktime(0, 0, 0, date("m")-$i+1, 1, date("Y")));
			if (!isset(${"vvl{$i}"})) ${"vvl{$i}"} = date("Y", mktime(0, 0, 0, date("m")-$i+1, 1, date("Y")));
			if (!isset(${"ppl{$i}"})) {
				if ($i == 1) ${"ppl{$i}"} = date("d");
				else ${"ppl{$i}"} = date("d", mktime(0, 0, 0, date("m")-($i-2), 0, date("Y")));
			}

			//Edellisen vuoden vastaavat kaudet
			${"kkaed{$i}"} = date("m", mktime(0, 0, 0, ${"kka{$i}"}, date("d"), ${"vva{$i}"}-1));
			${"ppaed{$i}"} = date("d", mktime(0, 0, 0, ${"kka{$i}"}, 1, ${"vva{$i}"}-1));
			${"vvaed{$i}"} = date("Y", mktime(0, 0, 0, ${"kka{$i}"}, date("d"), ${"vva{$i}"}-1));

			${"kkled{$i}"} = date("m", mktime(0, 0, 0, ${"kkl{$i}"}, date("d"), ${"vva{$i}"}-1));
			${"vvled{$i}"} = date("Y", mktime(0, 0, 0, ${"kkl{$i}"}, 1, ${"vva{$i}"}-1));
			${"ppled{$i}"} = date("d", mktime(0, 0, 0, ${"kkl{$i}"}+1, 0, ${"vva{$i}"}-1));

			//katotaan pienin alkupvm ja isoin loppupvm
			${"apaiva{$i}"} = (int) date('Ymd',mktime(0,0,0,${"kkaed{$i}"},${"ppaed{$i}"},${"vvaed{$i}"}));
			${"lpaiva{$i}"} = (int) date('Ymd',mktime(0,0,0,${"kkl{$i}"},${"ppl{$i}"},${"vvl{$i}"}));
		}

		$apienin = 99999999;
		$lsuurin = 0;

		for ($i = 1; $i < 13; $i++) {
			if (${"apaiva{$i}"} <= $apienin and ${"apaiva{$i}"} != 19700101) $apienin = ${"apaiva{$i}"};
			if (${"lpaiva{$i}"} >= $lsuurin and ${"lpaiva{$i}"} != 19700101) $lsuurin = ${"lpaiva{$i}"};
		}

		if ($apienin == 99999999 and $lsuurin == 0) {
			$apienin = $lsuurin = date('Ymd'); // jos mit��n ei l�ydy niin NOW molempiin. :)
		}

		$apvm = substr($apienin,0,4)."-".substr($apienin,4,2)."-".substr($apienin,6,2);
		$lpvm = substr($lsuurin,0,4)."-".substr($lsuurin,4,2)."-".substr($lsuurin,6,2);

		// Tulostettavat sarakkeet
		$sarakkeet = array();

		//Voidaan tarvita jotain muuttujaa t��lt�
		if (isset($muutparametrit)) {
			list($temp_osasto,$temp_tuoryh,$temp_ytunnus,$temp_tuotemerkki,$temp_asiakasosasto,$temp_asiakasno,$temp_toimittaja) = explode('#', $muutparametrit);
		}

		$sarakkeet["SARAKE1"] 	= t("osasto")."\t";
		$sarakkeet["SARAKE2"] 	= t("tuoteryhma")."\t";
		$sarakkeet["SARAKE3"] 	= t("tuotemerkki")."\t";
		$sarakkeet["SARAKE3B"] 	= t("malli")."\t";
		$sarakkeet["SARAKE3C"] 	= t("mallitarkenne")."\t";
		$sarakkeet["SARAKE4"] 	= t("tahtituote")."\t";
		$sarakkeet["SARAKE4B"] 	= t("status")."\t";
		$sarakkeet["SARAKE4C"] 	= t("abc")."\t";
		$sarakkeet["SARAKE4CA"] = t("abc osasto")."\t";
		$sarakkeet["SARAKE4CB"] = t("abc try")."\t";
		$sarakkeet["SARAKE4D"] 	= t("luontiaika")."\t";
		$sarakkeet["SARAKE5"] 	= t("saldo")."\t";
		$sarakkeet["SARAKE6"] 	= t("halytysraja")."\t";
		$sarakkeet["SARAKE6B"] 	= t("tilausmaara")."\t";
		$sarakkeet["SARAKE7"] 	= t("tilauksessa")."\t";
		$sarakkeet["SARAKE7A"] 	= t("saapuneet")."\t";
		$sarakkeet["SARAKE7B"] 	= t("valmistuksessa")."\t";
		$sarakkeet["SARAKE8"] 	= t("ennpois")."\t";
		$sarakkeet["SARAKE9"] 	= t("jt")."\t";
		$sarakkeet["SARAKE9A"]	= t("vapaa saldo")."\t";
		$sarakkeet["SARAKE9B"]	= t("myyt�viss�")."\t";

		$sarakkeet["SARAKE14"] 	= t("osto_era")."\t";
		$sarakkeet["SARAKE15"] 	= t("myynti_era")."\t";
		$sarakkeet["SARAKE16"] 	= t("toimittaja")."\t";
		$sarakkeet["SARAKE17"] 	= t("toim_tuoteno")."\t";
		$sarakkeet["SARAKE18"] 	= t("nimitys")."\t";
		$sarakkeet["SARAKE18B"] = t("toim_nimitys")."\t";
		$sarakkeet["SARAKE18C"] = t("kuvaus")."\t";

		$sarakkeet["SARAKE18D"] = t("lyhytkuvaus")."\t";
		$sarakkeet["SARAKE18E"] = t("tuotekorkeus")."\t";
		$sarakkeet["SARAKE18F"] = t("tuoteleveys")."\t";
		$sarakkeet["SARAKE18G"] = t("tuotesyvyys")."\t";
		$sarakkeet["SARAKE18H"] = t("tuotemassa")."\t";
		$sarakkeet["SARAKE18I"] = t("hinnastoon")."\t";

		$sarakkeet["SARAKE19"] 	= t("ostohinta")."\t";
		$sarakkeet["SARAKE20"] 	= t("myyntihinta")."\t";
		$sarakkeet["SARAKE20Z"] = t("epakurantti25pvm")."\t";
		$sarakkeet["SARAKE21"] 	= t("epakurantti50pvm")."\t";
		$sarakkeet["SARAKE21B"]	= t("epakurantti75pvm")."\t";
		$sarakkeet["SARAKE22"] 	= t("epakurantti100pvm")."\t";
		$sarakkeet["SARAKE23"] 	= t("oletussaldo")."\t";
		$sarakkeet["SARAKE24"] 	= t("hyllypaikka")."\t";

		$_x = 25;

		if ($tee == "RAPORTOI" and isset($RAPORTOI)) {
			for ($i = 1; $i < 13; $i++) {
				${"kuukausi{$i}"} = "(".${"ppa{$i}"}.${"kka{$i}"}.${"vva{$i}"}."-".${"ppl{$i}"}.${"kkl{$i}"}.${"vvl{$i}"}.")";
				${"kuukausied{$i}"} = "(".${"ppaed{$i}"}.${"kkaed{$i}"}.${"vvaed{$i}"}."-".${"ppled{$i}"}.${"kkled{$i}"}.${"vvled{$i}"}.")";
			}
		}
		else {
			for ($i = 1; $i < 13; $i++) {
				${"kuukausi{$i}"} = t("Kuukausi")." {$i}";
				${"kuukausied{$i}"} = t("Ed. Kuukausi")." {$i}";
			}
		}

		for ($i = 1; $i < 13; $i++) {
			$sarakkeet["SARAKE{$_x}"] = t("puutteet")." ".${"kuukausi{$i}"}."\t";
			$_x++;
		}

		//Myydyt kappaleet
		$_x_k = $_x;

		for ($i = 1; $i < 13; $i++) {
			$sarakkeet["SARAKE{$_x}"] = t("myynti")." ".${"kuukausi{$i}"}."\t";
			$_x++;
		}

		$sarakkeet["SARAKE{$_x}"] = t("myynti 12kk")."\t";
		$_x++;

		for ($i = 1; $i < 13; $i++) {
			$sarakkeet["SARAKE{$_x}"] = t("myynti")." ".${"kuukausied{$i}"}."\t";
			$_x++;
		}

		$sarakkeet["SARAKE{$_x}"] = t("myynti 24kk")."\t";
		$_x++;

		//Kulutetut kappaleet
		for ($i = 1; $i < 13; $i++) {
			$sarakkeet["SARAKE{$_x_k}K"] = t("kulutus")." ".${"kuukausi{$i}"}."\t";
			$_x_k++;
		}

		for ($i = 1; $i < 13; $i++) {
			$sarakkeet["SARAKE{$_x_k}K"] = t("kulutus")." ".${"kuukausied{$i}"}."\t";
			$_x_k++;
		}

		for ($i = 1; $i < 13; $i++) {
			$sarakkeet["SARAKE{$_x}"] = t("Kate")." {$yhtiorow['valkoodi']} ".${"kuukausi{$i}"}."\t";
			$_x++;
		}

		for ($i = 1; $i < 13; $i++) {
			$sarakkeet["SARAKE{$_x}"] = t("Kate %")." ".${"kuukausi{$i}"}."\t";
			$_x++;
		}

		$sarakkeet["SARAKE{$_x}"] 	= t("tuotekerroin")."\t";
		$_x++;
		$sarakkeet["SARAKE{$_x}"] 	= t("ennakkotilauksessa")."\t";
		$_x++;
		$sarakkeet["SARAKE{$_x}"] 	= t("aleryhm�")."\t";
		$sarakkeet["SARAKE{$_x}B"] = t("kehahin")."\t";
		$_x++;

		if ($temp_asiakasosasto != '' or $asiakasosasto != '') {
			for ($i = 1; $i < 13; $i++) {
				$sarakkeet["SARAKE{$_x}"] = t("myynti asiakasosasto")." {$asiakasosasto} ".${"kuukausi{$i}"}."\t";

				$_x++;
			}
		}

		if ($temp_asiakasno != '' or $asiakasno != '') {
			for ($i = 1; $i < 13; $i++) {
				$sarakkeet["SARAKE{$_x}"] = t("myynti asiakas")." {$asiakasno} ".${"kuukausi{$i}"}."\t";

				$_x++;
			}
		}

		$_x_k = $_x;

		$sarakkeet["SARAKE{$_x}"] = t("Korvaavat Tuoteno")."\t";
		$_x++;
		$sarakkeet["SARAKE{$_x}"] = t("Korvaavat Saldo")."\t";
		$_x++;
		$sarakkeet["SARAKE{$_x}"] = t("Korvaavat Ennpois")."\t";
		$_x++;
		$sarakkeet["SARAKE{$_x}"] = t("Korvaavat Tilauksessa")."\t";
		$_x++;

		for ($i = 1; $i < 13; $i++) {

			$sarakkeet["SARAKE{$_x}"] = t("Korvaavat Myyty")." ".${"kuukausi{$i}"}."\t";

			$_x++;
		}


		//	Haetaan kaikki varastot ja luodaan kysely paljonko ko. varastoon on tilattu tavaraa..
		$varastolisa = "";

		if ($valitut["OSTOTVARASTOITTAIN"] != "") {

			$query = "	SELECT *
						FROM varastopaikat
						WHERE yhtio = '$kukarow[yhtio]'
						ORDER BY tyyppi, nimitys";
			$osvres = pupe_query($query);

			$abuArray=array();

			while($vrow = mysql_fetch_assoc($osvres)) {
				$varastolisa .= ", sum(if (tyyppi='O' and
									concat(rpad(upper('$vrow[alkuhyllyalue]'),  5, '0'),lpad(upper('$vrow[alkuhyllynro]'),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0')) and
									concat(rpad(upper('$vrow[loppuhyllyalue]'), 5, '0'),lpad(upper('$vrow[loppuhyllynro]'), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
								, varattu, 0)) tilattu_$vrow[tunnus] ";

				$sarakkeet["SARAKE{$_x}#".$vrow["tunnus"]] = t("tilattu kpl - $vrow[nimitys]")."\t";
				$abuArray["SARAKE{$_x}#".$vrow["tunnus"]] = "SARAKE{$_x}#".$vrow["tunnus"];
			}

			// Liitet��n oletus jotta summat voisi t�sm�t�..
			$varastolisa .= ", sum(if (tyyppi='O' and hyllyalue = '' , varattu, 0)) tilattu_oletus ";

			$sarakkeet["SARAKE{$_x}#oletus"] = t("tilattu kpl - varastoa ei annettu")."\t";
			$abuArray["SARAKE{$_x}#oletus"] = "SARAKE65#oletus";

			//	karseeta haetaan offset valitut arrayksi jotta osataan siirt�� n�m� tiedot oikeaan paikkaan..
			$i = 0;

			$chk_array = array();

			for ($xxx = $_x_k; $xxx < $_x; $xxx++) {
				$chk_array[] = "SARAKE{$xxx}";
			}

			foreach($valitut as $key => $value) {
				if (in_array($key, $chk_array)) {
					$offset = $i;
					echo "l�ydettiin offset ($offset)<br>";
					break;
				}
				$i++;
			}
			array_splice($valitut,$offset,0,$abuArray);
		}

		//Jos halutaan tallentaa p�iv�m��r�t profiilin taakse
		if ($valitut["TALLENNAPAIVAM"] == "TALLENNAPAIVAM") {
			//Tehd��n p�iv�m��rist� tallennettavia
			$paivamaarat = array();

			for ($i = 1; $i < 13; $i++) {
				$paivamaarat[] = "ppa{$i}";
				$paivamaarat[] = "kka{$i}";
				$paivamaarat[] = "vva{$i}";
				$paivamaarat[] = "ppl{$i}";
				$paivamaarat[] = "kkl{$i}";
				$paivamaarat[] = "vvl{$i}";
			}

			foreach($paivamaarat as $paiva) {
				$valitut[] = "PAIVAM##".$paiva."##".${$paiva};
			}
		}


		// T�ss� luodaan uusi raporttiprofiili
		if ($tee == "RAPORTOI" and $uusirappari != '') {

			$rappari = $kukarow["kuka"]."##".$uusirappari;

			foreach($valitut as $val) {
				$query = "INSERT INTO avainsana set yhtio='$kukarow[yhtio]', laji='KKOSTOT', selite='$rappari', selitetark='$val'";
				$res = pupe_query($query);
			}
		}

		//Ajetaan itse raportti
		if ($tee == "RAPORTOI" and isset($RAPORTOI)) {

			if ($rappari != '') {
				$query = "DELETE FROM avainsana WHERE yhtio='$kukarow[yhtio]' and laji='KKOSTOT' and selite='$rappari'";
				$res = pupe_query($query);

				foreach($valitut as $val) {
					$query = "INSERT INTO avainsana set yhtio='$kukarow[yhtio]', laji='KKOSTOT', selite='$rappari', selitetark='$val'";
					$res = pupe_query($query);
				}
			}

			if ($tuoryh != '') {
				$sresult = t_avainsana("TRY", "", "and avainsana.selite  = '$tuoryh'");
				$srow = mysql_fetch_assoc($sresult);
			}
			if ($osasto != '') {
				$sresult = t_avainsana("OSASTO", "", "and avainsana.selite  = '$osasto'");
				$trow = mysql_fetch_assoc($sresult);
			}
			if ($toimittajaid != '') {
				$query = "	SELECT nimi
							FROM toimi
							WHERE yhtio = '$kukarow[yhtio]' and tunnus='$toimittajaid'";
				$sresult = pupe_query($query);
				$trow1 = mysql_fetch_assoc($sresult);
			}
			if ($asiakasid != '') {
				$query = "	SELECT nimi
							FROM asiakas
							WHERE yhtio = '$kukarow[yhtio]' and tunnus='$asiakasid'";
				$sresult = pupe_query($query);
				$trow2 = mysql_fetch_assoc($sresult);
			}

			$abcnimi = $ryhmanimet[$abcrajaus];

			echo "	<table>
					<tr><th>".t("Osasto")."</th><td colspan='3'>$osasto $trow[selitetark]</td></tr>
					<tr><th>".t("Tuoteryhm�")."</th><td colspan='3'>$tuoryh $srow[selitetark]</td></tr>
					<tr><th>".t("Toimittaja")."</th><td colspan='3'>$ytunnus $trow1[nimi]</td></tr>
					<tr><th>".t("Tuotemerkki")."</th><td colspan='3'>$tuotemerkki</td></tr>
					<tr><th>".t("ABC-rajaus")."</th><td colspan='3'>$abcnimi</td></tr>
					<tr><th>".t("Asiakasosasto")."</th><td colspan='3'>$asiakasosasto</td></tr>
					<tr><th>".t("Asiakas")."</th><td colspan='3'>$asiakasno $trow2[nimi]</td></tr>
					<tr><th>".t("JT")."</th><td colspan='3'>$KAIKKIJT</td></tr>";

			echo "	</table><br>";
			flush();

			$lisaa  = ""; // tuote-rajauksia
			$lisaa2 = ""; // toimittaja-rajauksia

			if ($osasto != '') {
				$lisaa .= " and tuote.osasto = '$osasto' ";
			}
			if ($tuoryh != '') {
				$lisaa .= " and tuote.try = '$tuoryh' ";
			}
			if ($tuotemerkki != '') {
				$lisaa .= " and tuote.tuotemerkki = '$tuotemerkki' ";
			}
			if ($valitut["poistetut"] != '') {
				$lisaa .= " and tuote.status != 'P' ";
			}
			if ($valitut["poistuvat"] != '') {
				$lisaa .= " and tuote.status != 'X' ";
			}
			if ($valitut["ei_ostoehd"] != '') {
				$lisaa .= " and tuote.ostoehdotus != 'E' ";
			}
			if ($valitut["EIHINNASTOON"] != '') {
				$lisaa .= " and tuote.hinnastoon != 'E' ";
			}
			if ($valitut["EIVARASTOITAVA"] != '') {
				$lisaa .= " and tuote.status != 'T' ";
			}
			// Listaa vain �skett�in perustetut tuotteet:
			if ($valitut["VAINUUDETTUOTTEET"] != '') {
				$lisaa .= " and tuote.luontiaika >= date_sub(current_date, interval 12 month) ";
			}
			// �l� listaa �skett�in perustettuja tuotteita:
			if ($valitut["UUDETTUOTTEET"] != '') {
				$lisaa .= " and tuote.luontiaika < date_sub(current_date, interval 12 month) ";
			}

			if ($toimittajaid != '') {
				$lisaa2 .= " JOIN tuotteen_toimittajat ON tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno and liitostunnus = '$toimittajaid' ";
			}

			///* T�m� skripti k�ytt�� slave-tietokantapalvelinta *///
			$useslave = 1;
			$usemastertoo = 1;

			// Eli haetaan connect.inc uudestaan t�ss�
			require("../inc/connect.inc");

			//Yhti�valinnat
			$query	= "	SELECT distinct yhtio, nimi
						from yhtio
						where konserni = '$yhtiorow[konserni]' and konserni != ''";
			$presult = pupe_query($query);

			$yhtiot 	= "";
			$konsyhtiot = "";

			if (mysql_num_rows($presult) > 0) {
				while ($prow = mysql_fetch_assoc($presult)) {
					if ($valitut["YHTIO##$prow[yhtio]"] == "YHTIO##".$prow["yhtio"]) {
						$yhtiot .= "'".$prow["yhtio"]."',";
					}
					$konsyhtiot .= "'".$prow["yhtio"]."',";
				}

				$yhtiot = substr($yhtiot,0,-1);
				$yhtiot = " yhtio in ($yhtiot) ";

				$konsyhtiot = substr($konsyhtiot,0,-1);
				$konsyhtiot = " yhtio in ($konsyhtiot) ";
			}
			else {
				$yhtiot = "'".$kukarow["yhtio"]."'";
				$yhtiot = " yhtio in ($yhtiot) ";

				$konsyhtiot = "'".$kukarow["yhtio"]."'";
				$konsyhtiot = " yhtio in ($konsyhtiot) ";
			}

			//Katsotaan valitut varastot
			$query = "	SELECT *
						FROM varastopaikat
						WHERE $konsyhtiot
						ORDER BY yhtio, tyyppi, nimitys";
			$vtresult = pupe_query($query);

			$varastot 			= "";
			$varastot_yhtiot 	= "";

			while ($vrow = mysql_fetch_assoc($vtresult)) {
				if ($valitut["VARASTO##$vrow[tunnus]"] == "VARASTO##".$vrow["tunnus"]) {
					$varastot .= "'".$vrow["tunnus"]."',";
					$varastot_yhtiot .= "'".$vrow["yhtio"]."',";
				}
			}

			$varastot 		 = substr($varastot,0,-1);
			$varastot_yhtiot = substr($varastot_yhtiot,0,-1);

			$paikoittain = $valitut["paikoittain"];

			if ($varastot == "" and $paikoittain != "") {
				echo "<font class='error'>".t("VIRHE: Ajat h�lytysraportin varastopaikoittain, mutta et valinnut yht��n varastoa.")."</font>";
				exit;
			}

			if ($varastot == "") {
				echo "<font class='error'>".t("VIRHE: Ajat h�lytysraportin, mutta et valinnut yht��n varastoa.")."</font>";
				exit;
			}

			if ($abcrajaus != "") {
				// katotaan JT:ss� olevat tuotteet
				$query = "	SELECT group_concat(distinct concat(\"'\",tilausrivi.tuoteno,\"'\") separator ',') tuotteet
							FROM tilausrivi USE INDEX (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
							JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno $lisaa)
							WHERE tilausrivi.$yhtiot
							and tyyppi 	= 'L'
							and var 	= 'J'
							and jt $lisavarattu > 0";
				$vtresult = pupe_query($query);
				$vrow = mysql_fetch_assoc($vtresult);

				$jt_tuotteet = "''";

				if ($vrow["tuotteet"] != "") {
					$jt_tuotteet = $vrow["tuotteet"];
				}

				// joinataan ABC-aputaulu katteen mukaan lasketun luokan perusteella
				$abcjoin = " 	JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (
								abc_aputaulu.yhtio = tuote.yhtio
								and abc_aputaulu.tuoteno = tuote.tuoteno
								and abc_aputaulu.tyyppi = '$abcrajaustapa'
								and (luokka <= '$abcrajaus' or luokka_osasto <= '$abcrajaus' or luokka_try <= '$abcrajaus' or tuote_luontiaika >= date_sub(current_date, interval 12 month) or abc_aputaulu.tuoteno in ($jt_tuotteet))) ";
			}
			else {
				$abcjoin = "	LEFT JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (
								abc_aputaulu.yhtio = tuote.yhtio
								and abc_aputaulu.tuoteno = tuote.tuoteno
								and abc_aputaulu.tyyppi = '$abcrajaustapa') ";
			}


			if ($KAIKKIJT == "KAIKKIJT") {
				// katotaan JT:ss� olevat tuotteet
				$query = "	SELECT group_concat(distinct concat(\"'\",tilausrivi.tuoteno,\"'\") separator ',') tuotteet
							FROM tilausrivi USE INDEX (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
							JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno $lisaa)
							WHERE tilausrivi.$yhtiot
							and tyyppi 	= 'L'
							and var 	= 'J'
							and jt $lisavarattu > 0";
				$vtresult = pupe_query($query);
				$vrow = mysql_fetch_assoc($vtresult);

				if ($vrow["tuotteet"] != "") {
					$lisaa .= " and tuote.tuoteno in ($vrow[tuotteet]) ";
				}
			}

			$varastot 		 = " HAVING varastopaikat.tunnus in ($varastot) or varastopaikat.tunnus is null ";
			$varastot_yhtiot = " yhtio in ($varastot_yhtiot) ";


			//Tuotekannassa voi olla tuotteen mitat kahdella eri tavalla
			// leveys x korkeus x syvyys
			// leveys x korkeus x pituus
			$query = "	SHOW columns
						FROM tuote
						LIKE 'tuotepituus'";
			$spres = pupe_query($query);

			if (mysql_num_rows($spres) == 1) {
				$splisa = "tuote.tuotepituus tuotesyvyys";
			}
			else {
				$splisa = "tuote.tuotesyvyys";
			}

			//Ajetaan raportti tuotteittain
			if ($paikoittain == '') {
				$query = "	SELECT
							tuote.yhtio,
							tuote.tuoteno,
							tuote.tilausmaara,
							tuote.tahtituote,
							tuote.status,
							tuote.nimitys,
							tuote.kuvaus,
							tuote.myynti_era,
							tuote.myyntihinta,
							tuote.epakurantti25pvm,
							tuote.epakurantti50pvm,
							tuote.epakurantti75pvm,
							tuote.epakurantti100pvm,
							tuote.tuotemerkki,
							tuote.malli,
							tuote.mallitarkenne,
							tuote.osasto,
							tuote.try,
							tuote.aleryhma,
							tuote.kehahin,
							abc_aputaulu.luokka abcluokka,
							abc_aputaulu.luokka_osasto abcluokka_osasto,
							abc_aputaulu.luokka_try abcluokka_try,
							tuote.luontiaika,
							tuote.sarjanumeroseuranta,
							tuote.tuotekorkeus,
							tuote.tuoteleveys,
							tuote.tuotemassa,
							$splisa,
							tuote.lyhytkuvaus,
							tuote.hinnastoon,
							SUM(tuotepaikat.halytysraja) AS halytysraja
							FROM tuote
							JOIN tuotepaikat ON (tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno)
							LEFT JOIN korvaavat ON tuote.yhtio = korvaavat.yhtio and tuote.tuoteno = korvaavat.tuoteno
							$lisaa2
							$abcjoin
							WHERE tuote.$yhtiot
							$lisaa
							and tuote.ei_saldoa = ''
							AND tuote.tuotetyyppi NOT IN ('A', 'B')
							GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31
							ORDER BY id, tuote.tuoteno";
			}
			//Ajetaan raportti tuotteittain, varastopaikoittain
			else {
				$query = "	SELECT
							tuote.yhtio,
							tuote.tuoteno,
							tuotepaikat.halytysraja,
							tuote.tilausmaara,
							tuote.tahtituote,
							tuote.status,
							tuote.nimitys,
							tuote.kuvaus,
							tuote.myynti_era,
							tuote.myyntihinta,
							tuote.epakurantti25pvm,
							tuote.epakurantti50pvm,
							tuote.epakurantti75pvm,
							tuote.epakurantti100pvm,
							tuote.tuotemerkki,
							tuote.malli,
							tuote.mallitarkenne,
							tuote.osasto,
							tuote.try,
							tuote.aleryhma,
							tuote.kehahin,
							abc_aputaulu.luokka abcluokka,
							abc_aputaulu.luokka_osasto abcluokka_osasto,
							abc_aputaulu.luokka_try abcluokka_try,
							tuote.luontiaika,
							tuote.sarjanumeroseuranta,
							tuote.tuotekorkeus,
							tuote.tuoteleveys,
							tuote.tuotemassa,
							$splisa,
							tuote.lyhytkuvaus,
							tuote.hinnastoon,
							concat_ws(' ',tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali,tuotepaikat.hyllytaso) varastopaikka,
							varastopaikat.tunnus
							FROM tuote
							$lisaa2
							$abcjoin
							JOIN tuotepaikat ON tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno
							LEFT JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
							and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
							and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
							LEFT JOIN korvaavat ON tuote.yhtio = korvaavat.yhtio and tuote.tuoteno = korvaavat.tuoteno
							WHERE tuote.$yhtiot
							$lisaa
							and tuote.ei_saldoa = ''
							AND tuote.tuotetyyppi NOT IN ('A', 'B')
							$varastot
							order by id, tuote.tuoteno, varastopaikka";
			}

			$res = pupe_query($query);

			if (isset($valitut["poistetut"]) and $valitut["poistetut"] != '' and isset($valitut["poistuvat"]) and $valitut["poistuvat"] != '') {
				echo "<font class='message'>".t("Vain aktiiviset tuotteet").".<br>";
			}
			if (isset($valitut["poistetut"]) and $valitut["poistetut"] != '' and !isset($valitut["poistuvat"])) {
				echo "<font class='message'>".t("Vain aktiiviset tuotteet, poistuvat n�ytet��n").".<br>";
			}
			if (!isset($valitut["poistetut"]) and isset($valitut["poistuvat"]) and $valitut["poistuvat"] != '') {
				echo "<font class='message'>".t("Vain aktiiviset tuotteet, poistetut n�ytet��n").".<br>";
			}

			if (isset($valitut["ei_ostoehd"]) and $valitut["ei_ostoehd"] != '') {
				echo "<font class='message'>".t("Vain ostoehdotettavat tuotteet").".<br>";
			}
			else {
				echo "<font class='message'>".t("Ostoehdotettavat tuotteet ja ostoehdotukseen kuulumattomat n�ytet��n").".<br>";
			}

			if (isset($valitut["OSTOTVARASTOITTAIN"]) and $valitut["OSTOTVARASTOITTAIN"] != '') {
				echo "<font class='message'>".t("Tilatut eritell��n varastoittain").".<br>";
			}

			if (isset($valitut["VAINUUDETTUOTTEET"]) and $valitut["VAINUUDETTUOTTEET"] != '') {
				echo "<font class='message'>".t("Listaa vain 12kk sis�ll� perustetut tuotteet").".<br>";
			}

			if (isset($valitut["UUDETTUOTTEET"]) and $valitut["UUDETTUOTTEET"] != '') {
				echo "<font class='message'>".t("Ei listata 12kk sis�ll� perustettuja tuotteita").".<br>";
			}

			if ($abcrajaus != "") {

				echo "<font class='message'>".t("ABC-luokka tai ABC-osastoluokka tai ABC-tuoteryhm�luokka")." >= $ryhmanimet[$abcrajaus] ".t("tai sit� on j�lkitoimituksessa");

				if ($valitut["VAINUUDETTUOTTEET"] == '' and $valitut["UUDETTUOTTEET"] == '') {
					echo " ".t("tai tuote on perustettu viimeisen 12kk sis�ll�").".<br>";
				}
				else {
					echo ".<br>";
				}
			}

			echo t("Tuotteita")." ".mysql_num_rows($res)." ".t("kpl").".<br>";

			if ($valitut["EHDOTETTAVAT"] != '') {
				echo "<font class='message'>".t("Joista j�tet��n pois ne tuotteet joita ei ehdoteta ostettavaksi").".<br>";
			}

			flush();

			require('inc/ProgressBar.class.php');

			include('inc/pupeExcel.inc');

			$worksheet 	 = new pupeExcel();
			$format_bold = array("bold" => TRUE);

			$rivi 		 = "";
			$excelrivi 	 = 0;
			$excelsarake = 0;

			$rivi .= t("tuoteno")."\t";

			$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("tuoteno")), $format_bold);
			$excelsarake++;

			if ($paikoittain != '') {
				$rivi .= t("Varastopaikka")."\t";

				$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("Varastopaikka")), $format_bold);
				$excelsarake++;
			}

			foreach ($valitut as $val) {
				$rivi .= $sarakkeet[$val];

				if ($sarakkeet[$val] != '') {
					$worksheet->writeString($excelrivi, $excelsarake, ucfirst(trim($sarakkeet[$val])), $format_bold);
					$excelsarake++;
				}
			}

			$rivi .= "\r\n";
			$excelrivi++;
			$excelsarake = 0;

			$bar = new ProgressBar();
			$bar->initialize(mysql_num_rows($res));

			while ($row = mysql_fetch_assoc($res)) {
				$bar->increase();

				$lisa = "";

				if ($paikoittain != '') {
					$lisa = " and concat_ws(' ',hyllyalue, hyllynro, hyllyvali, hyllytaso)='$row[varastopaikka]' ";
				}

				//toimittajatiedot
				if ($toimittajaid == '') {
					$query = "	SELECT group_concat(tuotteen_toimittajat.toimittaja order by tuotteen_toimittajat.tunnus separator '/') toimittaja,
								group_concat(distinct tuotteen_toimittajat.osto_era order by tuotteen_toimittajat.tunnus separator '/') osto_era,
								group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '/') toim_tuoteno,
								group_concat(distinct tuotteen_toimittajat.toim_nimitys order by tuotteen_toimittajat.tunnus separator '/') toim_nimitys,
								group_concat(distinct tuotteen_toimittajat.ostohinta order by tuotteen_toimittajat.tunnus separator '/') ostohinta,
								group_concat(distinct tuotteen_toimittajat.tuotekerroin order by tuotteen_toimittajat.tunnus separator '/') tuotekerroin
								FROM tuotteen_toimittajat
								WHERE yhtio = '$row[yhtio]'
								and tuoteno = '$row[tuoteno]'";
				}
				else {
					$query = "	SELECT tuotteen_toimittajat.toimittaja,
								tuotteen_toimittajat.osto_era,
								tuotteen_toimittajat.toim_tuoteno,
								tuotteen_toimittajat.toim_nimitys,
								tuotteen_toimittajat.ostohinta,
								tuotteen_toimittajat.tuotekerroin
								FROM tuotteen_toimittajat
								WHERE yhtio = '$row[yhtio]'
								and tuoteno = '$row[tuoteno]'
								and liitostunnus = '$toimittajaid'";
				}
				$result   = pupe_query($query);
				$toimirow = mysql_fetch_assoc($result);


				$row['toimittaja'] 		= $toimirow['toimittaja'];
				$row['osto_era'] 		= $toimirow['osto_era'];
				$row['toim_tuoteno'] 	= $toimirow['toim_tuoteno'];
				$row['toim_nimitys'] 	= $toimirow['toim_nimitys'];
				$row['ostohinta'] 		= $toimirow['ostohinta'];
				$row['tuotekerroin'] 	= $toimirow['tuotekerroin'];

				$selectlisa = "";

				for ($i = 1; $i < 13; $i++) {
					$selectlisa .= "sum(if (laskutettuaika >= '".${"vva{$i}"}."-".${"kka{$i}"}."-".${"ppa{$i}"}."' and laskutettuaika <= '".${"vvl{$i}"}."-".${"kkl{$i}"}."-".${"ppl{$i}"}."' ,kpl,0)) kpl{$i},";
					$selectlisa .= "sum(if (laskutettuaika >= '".${"vvaed{$i}"}."-".${"kkaed{$i}"}."-".${"ppaed{$i}"}."' and laskutettuaika <= '".${"vvled{$i}"}."-".${"kkled{$i}"}."-".${"ppled{$i}"}."' ,kpl,0)) EDkpl{$i},";
					$selectlisa .= "sum(if (laskutettuaika >= '".${"vva{$i}"}."-".${"kka{$i}"}."-".${"ppa{$i}"}."' and laskutettuaika <= '".${"vvl{$i}"}."-".${"kkl{$i}"}."-".${"ppl{$i}"}."' ,kate,0)) kate{$i},";
					$selectlisa .= "sum(if (laskutettuaika >= '".${"vva{$i}"}."-".${"kka{$i}"}."-".${"ppa{$i}"}."' and laskutettuaika <= '".${"vvl{$i}"}."-".${"kkl{$i}"}."-".${"ppl{$i}"}."' ,rivihinta,0)) rivihinta{$i}";

					if ($i < 12) $selectlisa .= ",";
				}

				///* Myydyt kappaleet *///
				$query = "	SELECT
							{$selectlisa}
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
							WHERE yhtio = '$row[yhtio]'
							and tyyppi	= 'L'
							and tuoteno = '$row[tuoteno]'
							and laskutettuaika >= '$apvm'
							and laskutettuaika <= '$lpvm'
							$lisa";
				$result   = pupe_query($query);
				$laskurow = mysql_fetch_assoc($result);

				$selectlisa = "";

				for ($i = 1; $i < 13; $i++) {

					$selectlisa .= "sum(if (laadittu >= '".${"vva{$i}"}."-".${"kka{$i}"}."-".${"ppa{$i}"}." 00:00:00' and laadittu <= '".${"vvl{$i}"}."-".${"kkl{$i}"}."-".${"ppl{$i}"}." 23:59:59' and var='P', tilkpl,0)) puutekpl{$i},";

					if ($i == 12) $selectlisa = substr($selectlisa, 0, -1);
				}

				$query = "	SELECT
							{$selectlisa}
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laadittu)
							WHERE yhtio = '$row[yhtio]'
							and tyyppi='L'
							and tuoteno = '$row[tuoteno]'
							and laadittu >= '$apvm 00:00:00'
							and laadittu <= '$lpvm 23:59:59'
							$lisa";
				$result   = pupe_query($query);
				$puuterow = mysql_fetch_assoc($result);

				for ($i = 1; $i < 13; $i++) {
					if ($laskurow["rivihinta{$i}"] <> 0) {
						${"katepros{$i}"} = round($laskurow["kate{$i}"] / $laskurow["rivihinta{$i}"] * 100,0);
					}
					else {
						${"katepros{$i}"} = 0;
					}
				}

				$selectlisa = "";

				for ($i = 1; $i < 13; $i++) {

					$selectlisa .= "sum(if (toimitettuaika >= '".${"vva{$i}"}."-".${"kka{$i}"}."-".${"ppa{$i}"}." 00:00:00' and toimitettuaika <= '".${"vvl{$i}"}."-".${"kkl{$i}"}."-".${"ppl{$i}"}." 23:59:59' ,kpl,0)) kpl{$i},";
					$selectlisa .= "sum(if (toimitettuaika >= '".${"vvaed{$i}"}."-".${"kkaed{$i}"}."-".${"ppaed{$i}"}." 00:00:00' and toimitettuaika <= '".${"vvled{$i}"}."-".${"kkled{$i}"}."-".${"ppled{$i}"}." 23:59:59' ,kpl,0)) EDkpl{$i}";

					if ($i < 12) $selectlisa .= ",";
				}

				///* Kulutetut kappaleet *///
				$query = "	SELECT
							{$selectlisa}
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laadittu)
							WHERE yhtio = '$row[yhtio]'
							and tyyppi='V'
							and tuoteno = '$row[tuoteno]'
							and ((toimitettuaika >= '$apvm 00:00:00' and toimitettuaika <= '$lpvm 23:59:59') or toimitettuaika = '0000-00-00 00:00:00')
							$lisa";
				$result   = pupe_query($query);
				$kulutrow = mysql_fetch_assoc($result);

				//tilauksessa, ennakkopoistot ja jt	HUOM: varastolisa m��ritelty jo aiemmin!
				$query = "	SELECT
							sum(if(tyyppi in ('W','M'), varattu, 0)) valmistuksessa,
							sum(if(tyyppi = 'O', varattu, 0)) tilattu,
							sum(if((tyyppi = 'O' and kpl = 0 and varattu != 0 and uusiotunnus != 0), varattu, 0)) saapuneet,
							sum(if(tyyppi = 'E', varattu, 0)) ennakot, # toimittamattomat ennakot
							sum(if(tyyppi in ('L','V') and var not in ('P','J','S'), varattu, 0)) ennpois,
							sum(if(tyyppi in ('L','G') and var in ('J','S'), jt $lisavarattu, 0)) jt
							$varastolisa
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
							WHERE yhtio = '$row[yhtio]'
		 					and tyyppi in ('L','V','O','G','E','W','M')
							and tuoteno = '$row[tuoteno]'
							and laskutettuaika = '0000-00-00'
							and (varattu+jt > 0)";
				$result = pupe_query($query);
				$ennp   = mysql_fetch_assoc($result);

				if ($paikoittain == '') {
					// Kaikkien valittujen varastojen paikkojen saldo yhteens�, mukaan tulee my�s aina ne saldot jotka ei kuulu mihink��n varastoalueeseen
					$query = "	SELECT sum(saldo) saldo, varastopaikat.tunnus
								FROM tuotepaikat
								LEFT JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
								and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
								and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
								WHERE tuotepaikat.$varastot_yhtiot
								and tuotepaikat.tuoteno='$row[tuoteno]'
								GROUP BY varastopaikat.tunnus
								$varastot";
					$result = pupe_query($query);

					$sumsaldo = 0;

					while($saldo = mysql_fetch_assoc($result)) {
						$sumsaldo += $saldo["saldo"];
					}

					$saldo["saldo"] = $sumsaldo;
				}
				else {
					// Ajetaan varastopaikoittain eli t�ss� on just t�n paikan saldo
					$query = "	SELECT saldo
								from tuotepaikat
								where yhtio='$row[yhtio]'
								and tuoteno='$row[tuoteno]'
								$lisa";
					$result = pupe_query($query);
					$saldo = mysql_fetch_assoc($result);
				}

				// oletuspaikan saldo ja hyllypaikka
				$query = "	SELECT sum(saldo) osaldo, hyllyalue, hyllynro, hyllyvali, hyllytaso
							from tuotepaikat
							where yhtio='$row[yhtio]'
							and tuoteno='$row[tuoteno]'
							and oletus='X'
							group by hyllyalue, hyllynro, hyllyvali, hyllytaso";
				$result = pupe_query($query);
				$osaldo = mysql_fetch_assoc($result);

				if ($row['osto_era']==0) $row['osto_era']=1;

				//asiakkaan ostot
				if ($asiakasosasto != '') {

					$selectlisa = "";

					for ($i = 1; $i < 13; $i++) {

						$selectlisa .= "sum(if (t.laskutettuaika >= '".${"vva{$i}"}."-".${"kka{$i}"}."-".${"ppa{$i}"}."' and t.laskutettuaika <= '".${"vvl{$i}"}."-".${"kkl{$i}"}."-".${"ppl{$i}"}."' ,t.kpl,0)) kpl{$i}";

						if ($i < 12) $selectlisa .= ",";
					}

					$query  = "	SELECT
								{$selectlisa}
								FROM tilausrivi t use index (yhtio_tyyppi_tuoteno_laskutettuaika),
								lasku l use index(PRIMARY),
								asiakas a use index (ytunnus_index)
								WHERE t.yhtio = '$row[yhtio]'
								and t.tyyppi = 'L'
								and t.tuoteno = '$row[tuoteno]'
								and t.laskutettuaika >= '$apvm'
								and t.laskutettuaika <= '$lpvm'
								and l.yhtio = t.yhtio
								and l.tunnus = t.uusiotunnus
								and a.ytunnus = l.ytunnus
								and a.yhtio = l.yhtio
								and a.osasto = '$asiakasosasto'";
					$asosresult = pupe_query($query);
					$asosrow = mysql_fetch_assoc($asosresult);
				}

				if ($asiakasid != '') {

					$selectlisa = "";

					for ($i = 1; $i < 13; $i++) {

						$selectlisa .= "sum(if (t.laskutettuaika >= '".${"vva{$i}"}."-".${"kka{$i}"}."-".${"ppa{$i}"}."' and t.laskutettuaika <= '".${"vvl{$i}"}."-".${"kkl{$i}"}."-".${"ppl{$i}"}."' ,t.kpl,0)) kpl{$i}";

						if ($i < 12) $selectlisa .= ",";
					}

					$query  = "	SELECT
								{$selectlisa}
								FROM tilausrivi t use index (yhtio_tyyppi_tuoteno_laskutettuaika),
								lasku l use index(PRIMARY)
								WHERE t.yhtio = '$row[yhtio]'
								and t.tyyppi = 'L'
								and t.tuoteno = '$row[tuoteno]'
								and t.laskutettuaika >= '$apvm'
								and t.laskutettuaika <= '$lpvm'
								and l.yhtio = t.yhtio
								and l.tunnus = t.otunnus
								and l.liitostunnus 	= '$asiakasid'";
					$asresult = pupe_query($query);
					$asrow = mysql_fetch_assoc($asresult);
				}

				if ($valitut['EHDOTETTAVAT'] == '' or $ostettavahalytilausmaara > 0 or $ostettavahaly > 0 or $ostettava4kk > 0) {

					// kirjotettaan rivi
					$rivi .= "\"$row[tuoteno]\"\t";

					$worksheet->writeString($excelrivi, $excelsarake, $row["tuoteno"], $format_bold);
					$excelsarake++;

					if ($paikoittain != '') {
						$rivi .= "\"$row[varastopaikka]\"\t";

						$worksheet->writeString($excelrivi, $excelsarake, $row["varastopaikka"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE1"] != '') {
						$rivi .= "\"$row[osasto]\"\t";

						$worksheet->write($excelrivi, $excelsarake, $row["osasto"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE2"] != '') {
						$rivi .= "\"$row[try]\"\t";

						$worksheet->write($excelrivi, $excelsarake, $row["try"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE3"] != '') {
						$rivi .= "\"$row[tuotemerkki]\"\t";

						$worksheet->writeString($excelrivi, $excelsarake, $row["tuotemerkki"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE3B"] != '') {
						$rivi .= "\"$row[malli]\"\t";

						$worksheet->writeString($excelrivi, $excelsarake, $row["malli"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE3C"] != '') {
						$rivi .= "\"$row[mallitarkenne]\"\t";

						$worksheet->writeString($excelrivi, $excelsarake, $row["mallitarkenne"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE4"] != '') {
						$rivi .= "\"$row[tahtituote]\"\t";

						$worksheet->writeString($excelrivi, $excelsarake, $row["tahtituote"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE4B"] != '') {
						$rivi .= "\"$row[status]\"\t";

						$worksheet->writeString($excelrivi, $excelsarake, $row["status"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE4C"] != '') {
						$rivi .= "\"".$ryhmanimet[$row["abcluokka"]]."\"\t";

						$worksheet->writeString($excelrivi, $excelsarake, $ryhmanimet[$row["abcluokka"]]);
						$excelsarake++;
					}

					if ($valitut["SARAKE4CA"] != '') {
						$rivi .= "\"".$ryhmanimet[$row["abcluokka_osasto"]]."\"\t";

						$worksheet->write($excelrivi, $excelsarake, $ryhmanimet[$row["abcluokka_osasto"]]);
						$excelsarake++;
					}

					if ($valitut["SARAKE4CB"] != '') {
						$rivi .= "\"".$ryhmanimet[$row["abcluokka_try"]]."\"\t";

						$worksheet->write($excelrivi, $excelsarake, $ryhmanimet[$row["abcluokka_try"]]);
						$excelsarake++;
					}

					if ($valitut["SARAKE4D"] != '') {
						if ($row["luontiaika"] == "0000-00-00 00:00:00") $row["luontiaika"] = "";

						$rivi .= "\"$row[luontiaika]\"\t";

						$worksheet->writeString($excelrivi, $excelsarake, $row["luontiaika"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE5"] != '') {
						$rivi .= str_replace(".",",",$saldo['saldo'])."\t";

						$worksheet->writeNumber($excelrivi, $excelsarake, $saldo["saldo"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE6"] != '') {
						$rivi .= str_replace(".",",",$row['halytysraja'])."\t";

						$worksheet->writeNumber($excelrivi, $excelsarake, $row["halytysraja"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE6B"] != '') {
						$rivi .= str_replace(".",",",$row['tilausmaara'])."\t";

						$worksheet->writeNumber($excelrivi, $excelsarake, $row["tilausmaara"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE7"] != '') {
						$rivi .= str_replace(".",",",$ennp['tilattu'])."\t";

						$worksheet->writeNumber($excelrivi, $excelsarake, $ennp["tilattu"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE7A"] != '') {
						$rivi .= str_replace(".",",",$ennp['saapuneet'])."\t";

						$worksheet->writeNumber($excelrivi, $excelsarake, $ennp["saapuneet"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE7B"] != '') {
						$rivi .= str_replace(".",",",$ennp['valmistuksessa'])."\t";

						$worksheet->writeNumber($excelrivi, $excelsarake, $ennp["valmistuksessa"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE8"] != '') {
						$rivi .= str_replace(".",",",$ennp['ennpois'])."\t";

						$worksheet->writeNumber($excelrivi, $excelsarake, $ennp["ennpois"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE9"] != '') {
						$rivi .= str_replace(".",",",$ennp['jt'])."\t";

						$worksheet->writeNumber($excelrivi, $excelsarake, $ennp["jt"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE9A"] != '') {

						$vapaa_saldo = $saldo['saldo'] + $ennp['tilattu'] - $ennp['ennpois'];

						if ($yhtiorow['varaako_jt_saldoa'] == 'K') $vapaa_saldo -= $ennp['jt'];

						$rivi .= str_replace(".",",",$vapaa_saldo)."\t";

						$worksheet->writeNumber($excelrivi, $excelsarake, $vapaa_saldo);
						$excelsarake++;
					}

					if ($valitut["SARAKE9B"] != '') {

						$myytavissa = $saldo['saldo'] - $ennp['ennpois'];

						if ($yhtiorow['varaako_jt_saldoa'] == 'K') $myytavissa -= $ennp['jt'];

						$rivi .= str_replace(".",",",$myytavissa)."\t";

						$worksheet->writeNumber($excelrivi, $excelsarake, $myytavissa);
						$excelsarake++;
					}

					if ($valitut["SARAKE14"] != '') {
						$rivi .= str_replace(".",",",$row['osto_era'])."\t";

						$worksheet->writeNumber($excelrivi, $excelsarake, $row["osto_era"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE15"] != '') {
						$rivi .= str_replace(".",",",$row['myynti_era'])."\t";

						$worksheet->writeNumber($excelrivi, $excelsarake, $row["myynti_era"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE16"] != '') {
						$rivi .= "\"$row[toimittaja]\"\t";

						$worksheet->writeString($excelrivi, $excelsarake, $row["toimittaja"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE17"] != '') {
						$rivi .= "\"$row[toim_tuoteno]\"\t";

						$worksheet->writeString($excelrivi, $excelsarake, $row["toim_tuoteno"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE18"] != '') {
						$rivi .= "\"".t_tuotteen_avainsanat($row, 'nimitys')."\"\t";

						$worksheet->writeString($excelrivi, $excelsarake, t_tuotteen_avainsanat($row, 'nimitys'));
						$excelsarake++;
					}

					if ($valitut["SARAKE18B"] != '') {
						$rivi .= "\"$row[toim_nimitys]\"\t";

						$worksheet->writeString($excelrivi, $excelsarake, $row["toim_nimitys"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE18C"] != '') {
						$rivi .= "\"$row[kuvaus]\"\t";

						$worksheet->writeString($excelrivi, $excelsarake, $row["kuvaus"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE18D"] != '') {
						$rivi .= "\"$row[lyhytkuvaus]\"\t";

						$worksheet->writeString($excelrivi, $excelsarake, $row["lyhytkuvaus"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE18E"] != '') {
						$rivi .= "\"$row[tuotekorkeus]\"\t";

						$worksheet->writeNumber($excelrivi, $excelsarake, $row["tuotekorkeus"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE18F"] != '') {
						$rivi .= "\"$row[tuoteleveys]\"\t";

						$worksheet->writeNumber($excelrivi, $excelsarake, $row["tuoteleveys"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE18G"] != '') {
						$rivi .= "\"$row[tuotesyvyys]\"\t";

						$worksheet->writeNumber($excelrivi, $excelsarake, $row["tuotesyvyys"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE18H"] != '') {
						$rivi .= "\"$row[tuotemassa]\"\t";

						$worksheet->writeNumber($excelrivi, $excelsarake, $row["tuotemassa"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE18I"] != '') {
						$rivi .= "\"$row[hinnastoon]\"\t";

						$worksheet->writeString($excelrivi, $excelsarake, $row["hinnastoon"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE19"] != '') {
						$rivi .= str_replace(".",",",$row['ostohinta'])."\t";

						$worksheet->writeNumber($excelrivi, $excelsarake, $row["ostohinta"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE20"] != '') {
						$rivi .= str_replace(".",",",$row['myyntihinta'])."\t";

						$worksheet->writeNumber($excelrivi, $excelsarake, $row["myyntihinta"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE20Z"] != '') {
						$epakur = "";
						if ($row['epakurantti25pvm'] != '0000-00-00') $epakur = $row['epakurantti25pvm'];

						$rivi .= "$epakur\t";

						$worksheet->writeString($excelrivi, $excelsarake, $epakur);
						$excelsarake++;
					}

					if ($valitut["SARAKE21"] != '') {
						$epakur = "";
						if ($row['epakurantti50pvm'] != '0000-00-00') $epakur = $row['epakurantti50pvm'];

						$rivi .= "$epakur\t";

						$worksheet->writeString($excelrivi, $excelsarake, $epakur);
						$excelsarake++;
					}

					if ($valitut["SARAKE21B"] != '') {
						$epakur = "";
						if ($row['epakurantti75pvm'] != '0000-00-00') $epakur = $row['epakurantti75pvm'];

						$rivi .= "$epakur\t";

						$worksheet->writeString($excelrivi, $excelsarake, $epakur);
						$excelsarake++;
					}

					if ($valitut["SARAKE22"] != '') {
						$epakur = "";
						if ($row['epakurantti100pvm'] != '0000-00-00') $epakur = $row['epakurantti100pvm'];

						$rivi .= "$epakur\t";

						$worksheet->writeString($excelrivi, $excelsarake, $epakur);
						$excelsarake++;
					}

					if ($valitut["SARAKE23"] != '') {
						$rivi .= str_replace(".",",",$osaldo['osaldo'])."\t";

						$worksheet->writeNumber($excelrivi, $excelsarake, $osaldo["osaldo"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE24"] != '') 	{
						$rivi .= "\"$osaldo[hyllyalue]-$osaldo[hyllynro]-$osaldo[hyllyvali]-$osaldo[hyllytaso]\"\t";

						$worksheet->writeString($excelrivi, $excelsarake, "$osaldo[hyllyalue]-$osaldo[hyllynro]-$osaldo[hyllyvali]-$osaldo[hyllytaso]");
						$excelsarake++;
					}

					$_x = 25;

					for ($i = 1; $i < 13; $i++) {

						if ($valitut["SARAKE{$_x}"] != '') 	{
							$rivi .= str_replace(".",",",$puuterow["puutekpl{$i}"])."\t";
							$worksheet->writeNumber($excelrivi, $excelsarake, $puuterow["puutekpl{$i}"]);
							$excelsarake++;
						}

						$_x++;
					}

					$_x_k = $_x;

					$myydyt_kappaleet_yhteensa = 0;

					//Myydyt kappaleet
					for ($i = 1; $i < 13; $i++) {

						if ($valitut["SARAKE{$_x}"] != '') {
							$rivi .= str_replace(".",",",$laskurow["kpl{$i}"])."\t";
							$worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["kpl{$i}"]);
							$myydyt_kappaleet_yhteensa += $laskurow["kpl{$i}"];
							$excelsarake++;
						}

						$_x++;
					}

					if ($valitut["SARAKE{$_x}"] != "") {
						$rivi .= str_replace(".",",",$myydyt_kappaleet_yhteensa)."\t";
						$worksheet->writeNumber($excelrivi, $excelsarake, $myydyt_kappaleet_yhteensa);
						$excelsarake++;
					}

					$_x++;

					for ($i = 1; $i < 13; $i++) {

						if ($valitut["SARAKE{$_x}"] != '') {
							$rivi .= str_replace(".",",",$laskurow["EDkpl{$i}"])."\t";
							$worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["EDkpl{$i}"]);
							$myydyt_kappaleet_yhteensa += $laskurow["EDkpl{$i}"];
							$excelsarake++;
						}

						$_x++;
					}

					if ($valitut["SARAKE{$_x}"] != "") {
						$rivi .= str_replace(".",",",$myydyt_kappaleet_yhteensa)."\t";
						$worksheet->writeNumber($excelrivi, $excelsarake, $myydyt_kappaleet_yhteensa);
						$excelsarake++;
					}

					$_x++;

					//Kulutetut kappaleet
					for ($i = 1; $i < 13; $i++) {

						if ($valitut["SARAKE{$_x_k}K"] != '') {
							$rivi .= str_replace(".",",",$kulutrow["kpl{$i}"])."\t";

							$worksheet->writeNumber($excelrivi, $excelsarake, $kulutrow["kpl{$i}"]);
							$excelsarake++;
						}

						$_x_k++;
					}

					for ($i = 1; $i < 13; $i++) {
						if ($valitut["SARAKE{$_x_k}K"] != '') {
							$rivi .= str_replace(".",",",$kulutrow["EDkpl{$i}"])."\t";

							$worksheet->writeNumber($excelrivi, $excelsarake, $kulutrow["EDkpl{$i}"]);
							$excelsarake++;
						}

						$_x_k++;
					}

					for ($i = 1; $i < 13; $i++) {
						if ($valitut["SARAKE{$_x}"] != '') {
							$rivi .= str_replace(".",",",$laskurow["kate{$i}"])."\t";

							$worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["kate{$i}"]);
							$excelsarake++;
						}

						$_x++;
					}

					for ($i = 1; $i < 13; $i++) {
						if ($valitut["SARAKE{$_x}"] != '') {
							$rivi .= str_replace(".",",",${"katepros{$i}"})."\t";

							$worksheet->writeNumber($excelrivi, $excelsarake, ${"katepros{$i}"});
							$excelsarake++;
						}

						$_x++;
					}

					if ($valitut["SARAKE{$_x}"] != '') {
						$rivi .= str_replace(".",",",$row['tuotekerroin'])."\t";

						$worksheet->writeNumber($excelrivi, $excelsarake, $row["tuotekerroin"]);
						$excelsarake++;
					}

					$_x++;

					if ($valitut["SARAKE{$_x}"] != '') {
						$rivi .= str_replace(".",",",$ennp["ennakot"])."\t";

						$worksheet->writeNumber($excelrivi, $excelsarake, $ennp["ennakot"]);
						$excelsarake++;
					}

					$_x++;

					if ($valitut["SARAKE{$_x}"] != '') {
						$rivi .= "\"$row[aleryhma]\"\t";

						$worksheet->writeString($excelrivi, $excelsarake, $row["aleryhma"]);
						$excelsarake++;
					}

					if ($valitut["SARAKE{$_x}B"] != '') {
						$kehahin = 0;

						//Jos tuote on sarjanumeroseurannassa niin kehahinta lasketaan yksil�iden ostohinnoista (ostetut yksil�t jotka eiv�t viel� ole myyty(=laskutettu))
						if ($row["sarjanumeroseuranta"] == "S") {
							$query	= "	SELECT avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl) kehahin
										FROM sarjanumeroseuranta
										LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
										LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
										WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
										and sarjanumeroseuranta.tuoteno = '$row[tuoteno]'
										and sarjanumeroseuranta.myyntirivitunnus != -1
										and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
										and tilausrivi_osto.laskutettuaika != '0000-00-00'";
							$sarjares = pupe_query($query);
							$sarjarow = mysql_fetch_assoc($sarjares);

							$kehahin = sprintf('%.2f', $sarjarow["kehahin"]);
						}
						else {
							$kehahin = sprintf('%.2f', $row["kehahin"]);
						}

						if 		($row['epakurantti100pvm'] != '0000-00-00') $kehahin = 0;
						elseif 	($row['epakurantti75pvm'] != '0000-00-00')  $kehahin = round($kehahin * 0.25, 6);
						elseif 	($row['epakurantti50pvm'] != '0000-00-00')  $kehahin = round($kehahin * 0.5,  6);
						elseif 	($row['epakurantti25pvm'] != '0000-00-00')  $kehahin = round($kehahin * 0.75, 6);

						$rivi .= str_replace(".",",",$kehahin)."\t";

						$worksheet->writeNumber($excelrivi, $excelsarake, $kehahin);
						$excelsarake++;
					}

					$_x++;

					if ($asiakasosasto != '') {

						for ($i = 1; $i < 13; $i++) {

							if ($valitut["SARAKE{$_x}"] != '') {
								$rivi .= str_replace(".",",",$asosrow["kpl{$i}"])."\t";

								$worksheet->writeNumber($excelrivi, $excelsarake, $asosrow["kpl{$i}"]);
								$excelsarake++;
							}

							$_x++;
						}
					}

					if ($asiakasno != '') {

						for ($i = 1; $i < 13; $i++) {

							if ($valitut["SARAKE{$_x}"] != '') {
								$rivi .= str_replace(".",",",$asrow["kpl{$i}"])."\t";

								$worksheet->writeNumber($excelrivi, $excelsarake, $asrow["kpl{$i}"]);
								$excelsarake++;
							}

							$_x++;
						}
					}

					unset($korvaresult1);
					unset($korvaresult2);
					unset($korvaavat_tunrot);

					//korvaavat tuotteet
					$query  = "	SELECT id
								FROM korvaavat
								WHERE tuoteno	= '$row[tuoteno]'
								and yhtio		= '$row[yhtio]'";
					$korvaresult1 = pupe_query($query);

					if (mysql_num_rows($korvaresult1) > 0) {
						$korvarow = mysql_fetch_assoc($korvaresult1);

						$query  = "	SELECT tuoteno
									FROM korvaavat
									WHERE tuoteno  != '$row[tuoteno]'
									and id			= '$korvarow[id]'
									and yhtio		= '$row[yhtio]'";
						$korvaresult2 = pupe_query($query);

						$korvaavat_tunrot = "";

						//tulostetaan korvaavat
						while ($korvarow = mysql_fetch_assoc($korvaresult2)) {
							$korvaavat_tunrot .= ",'$korvarow[tuoteno]'";
						}
					}

					//Liitet��nk� my�s tilauttu by varasto
					if (is_resource($osvres)) {
						mysql_data_seek($osvres, 0);

						while($vrow = mysql_fetch_assoc($osvres)) {
							$rivi .= str_replace(".",",",$ennp["tilattu_".$vrow["tunnus"]])."\t";

							$worksheet->write($excelrivi, $excelsarake, $ennp["tilattu_".$vrow["tunnus"]]);
							$excelsarake++;

							$_x++;
						}

						$rivi .= str_replace(".",",",$ennp["tilattu_oletus"])."\t";

						$worksheet->writeNumber($excelrivi, $excelsarake, $ennp["tilattu_oletus"]);
						$excelsarake++;

						$_x++;
					}


					if (is_resource($korvaresult2) and mysql_num_rows($korvaresult2) > 0) {

						mysql_data_seek($korvaresult2, 0);

						//tulostetaan korvaavat
						while ($korvarow = mysql_fetch_assoc($korvaresult2)) {
							// Korvaavien paikkojen valittujen varastojen paikkojen saldo yhteens�, mukaan tulee my�s aina ne saldot jotka ei kuulu mihink��n varastoalueeseen
							$query = "	SELECT sum(saldo) saldo, varastopaikat.tunnus
										FROM tuotepaikat
										LEFT JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
										and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
										and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
										WHERE tuotepaikat.$varastot_yhtiot
										and tuotepaikat.tuoteno='$korvarow[tuoteno]'
										GROUP BY varastopaikat.tunnus
										$varastot";
							$korvasaldoresult = pupe_query($query);

							$korva_sumsaldo = 0;

							while($korvasaldorow = mysql_fetch_assoc($korvasaldoresult)) {
								$korva_sumsaldo += $korvasaldorow["saldo"];
							}

							$korvasaldorow["saldo"] = $korva_sumsaldo;

							// Saldolaskentaa tulevaisuuteen
							$query = "	SELECT
										sum(if(tyyppi in ('O','W','M'), varattu, 0)) tilattu,
										sum(if(tyyppi in ('L','V'), varattu, 0)) varattu
										FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
										WHERE yhtio='$row[yhtio]'
										and tyyppi in ('L','V','O','W','M')
										and tuoteno='$korvarow[tuoteno]'
										and varattu>0";
							$presult = pupe_query($query);
							$prow = mysql_fetch_assoc($presult);

							//Korvaavien myynnnit
							$query  = "	SELECT
										sum(if (laskutettuaika >= '$vva1-$kka1-$ppa1' and laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,kpl,0)) kpl1,
										sum(if (laskutettuaika >= '$vva2-$kka2-$ppa2' and laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,kpl,0)) kpl2,
										sum(if (laskutettuaika >= '$vva3-$kka3-$ppa3' and laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,kpl,0)) kpl3,
										sum(if (laskutettuaika >= '$vva4-$kka4-$ppa4' and laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,kpl,0)) kpl4
										FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
										WHERE yhtio = '$row[yhtio]'
										and tyyppi = 'L'
										and tuoteno = '$korvarow[tuoteno]'
										and laskutettuaika >= '$apvm'
										and laskutettuaika <= '$lpvm'";
							$asresult = pupe_query($query);
							$kasrow = mysql_fetch_assoc($asresult);

							if ($valitut["SARAKE{$_x}"] != '') {
								$rivi .= "\"$korvarow[tuoteno]\"\t";

								$worksheet->writeString($excelrivi, $excelsarake, $korvarow["tuoteno"], $format_bold);
								$excelsarake++;
							}

							$_x++;

							if ($valitut["SARAKE{$_x}"] != '') {
								$rivi .= str_replace(".",",",$korvasaldorow['saldo'])."\t";

								$worksheet->writeNumber($excelrivi, $excelsarake, $korvasaldorow["saldo"]);
								$excelsarake++;
							}

							$_x++;

							if ($valitut["SARAKE{$_x}"] != '') {
								$rivi .= str_replace(".",",",$prow['varattu'])."\t";

								$worksheet->writeNumber($excelrivi, $excelsarake, $prow["varattu"]);
								$excelsarake++;
							}

							$_x++;

						    if ($valitut["SARAKE{$_x}"] != '') {
								$rivi .= str_replace(".",",",$prow['tilattu'])."\t";

								$worksheet->writeNumber($excelrivi, $excelsarake, $prow["tilattu"]);
								$excelsarake++;
							}

							$_x++;

							for ($i = 1; $i < 13; $i++) {
								if ($valitut["SARAKE{$_x}"] != '') {
									$rivi .= str_replace(".",",",$kasrow["kpl{$i}"])."\t";

									$worksheet->writeNumber($excelrivi, $excelsarake, $kasrow["kpl{$i}"]);
									$excelsarake++;
								}

								$_x++;
							}
						}
					}

					$rivi .= "\r\n";
					$excelrivi++;
					$excelsarake = 0;
				}
			}

			flush();

			echo "<br>";

			$excelnimi = $worksheet->close();

			echo "<table>";
			echo "<tr><th>".t("Tallenna raportti (xlsx)").":</th>";
			echo "<form method='post' class='multisubmit'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='Kuukautisraportti.xlsx'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br>";

			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$txtnimi = md5(uniqid(mt_rand(), true)).".txt";

			file_put_contents("/tmp/$txtnimi", $rivi);

			echo "<table>";
			echo "<tr><th>".t("Tallenna raportti (txt)").":</th>";
			echo "<form method='post' class='multisubmit'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='Kuukautisraportti.txt'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$txtnimi'>";
			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br>";

			echo "<table>";
			echo "<tr><th>".t("Tallenna raportti (csv)").":</th>";
			echo "<form method='post' class='multisubmit'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='Kuukautisraportti.csv'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$txtnimi'>";
			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br>";


			// N�� muuttujat voi olla aika isoja joten unsetataan ne
			unset($rivi);

			$osasto		 = '';
			$tuoryh		 = '';
			$ytunnus	 = '';
			$tuotemerkki = '';
			$tee		 = 'X';
		}

		if ($tee == "" or $tee == "JATKA") {
			if (isset($muutparametrit)) {
				list($osasto,$tuoryh,$ytunnus,$tuotemerkki,$asiakasosasto,$asiakasno,$toimittaja) = explode('#', $muutparametrit);
			}

			$muutparametrit = $osasto."#".$tuoryh."#".$ytunnus."#".$tuotemerkki."#".$asiakasosasto."#".$asiakasno."#";

			if ($tuoryh !='' or $osasto != '' or $ytunnus != '' or $tuotemerkki != '' or $KAIKKIJT != '') {
				if ($ytunnus != '' and !isset($ylatila)) {

					require("../inc/kevyt_toimittajahaku.inc");

					if ($ytunnus != '') {
						$tee = "JATKA";
					}
				}
				elseif ($ytunnus != '' and isset($ylatila)) {
					$tee = "JATKA";
				}
				elseif ($tuoryh !='' or $osasto != '' or $tuotemerkki != '' or $KAIKKIJT != '') {
					$tee = "JATKA";
				}
				else {
					$tee = "";
				}
			}

			$muutparametrit = $osasto."#".$tuoryh."#".$ytunnus."#".$tuotemerkki."#".$asiakasosasto."#".$asiakasno."#";

			if ($asiakasno != '' and $tee == "JATKA") {
				$muutparametrit .= $ytunnus;

				if ($asiakasid == "") {
					$ytunnus = $asiakasno;
				}

				require ("inc/asiakashaku.inc");

				if ($ytunnus != '') {
					$tee = "JATKA";
					$asiakasno = $ytunnus;
					$ytunnus = $toimittaja;
				}
				else {
					$asiakasno = $ytunnus;
					$ytunnus = $toimittaja;

					$tee = "";
				}
			}
		}

		if ($tee == "") {

			echo "	<form method='post' autocomplete='off'>
					<br>",t("Valitse v�hint��n yksi seuraavista:"),"
					<table>
					<tr><th>",t("Osasto"),"</th><td>";

			// tehd��n avainsana query
			$sresult = t_avainsana("OSASTO");

			echo "<select name='osasto'>";
			echo "<option value=''>",t("N�yt� kaikki"),"</option>";

			while ($srow = mysql_fetch_assoc($sresult)) {
				$sel = '';
				if ($osasto == $srow["selite"]) {
					$sel = "selected";
				}
				echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
			}
			echo "</select>";


			echo "</td></tr>
					<tr><th>".t("Tuoteryhm�")."</th><td>";

			//Tehd��n osasto & tuoteryhm� pop-upit
			// tehd��n avainsana query
			$sresult = t_avainsana("TRY");

			echo "<select name='tuoryh'>";
			echo "<option value=''>".t("N�yt� kaikki")."</option>";

			while ($srow = mysql_fetch_assoc($sresult)) {
				$sel = '';
				if ($tuoryh == $srow["selite"]) {
					$sel = "selected";
				}
				echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
			}
			echo "</select>";


			echo "</td></tr>
					<tr><th>".t("Tuotemerkki")."</th><td>";

			//Tehd��n osasto & tuoteryhm� pop-upit
			$sresult = t_avainsana("TUOTEMERKKI");

			echo "<select name='tuotemerkki'>";
			echo "<option value=''>".t("N�yt� kaikki")."</option>";

			while ($srow = mysql_fetch_assoc($sresult)) {
				$sel = '';
				if ($tuotemerkki == $srow["selite"]) {
					$sel = "selected";
				}
				echo "<option value='$srow[selite]' $sel>$srow[selite]</option>";
			}
			echo "</select>";


			echo "</td></tr>";
			echo "<tr><th>".t("Toimittaja")."</th><td><input type='text' size='20' name='ytunnus' value='$ytunnus'></td></tr>";

			echo "<tr><th>".t("ABC-luokkarajaus ja rajausperuste")."</th><td>";

			echo "<select name='abcrajaus'>";
			echo "<option  value=''>".t("Valitse")."</option>";

			$teksti = "";
			for ($i=0; $i < count($ryhmaprossat); $i++) {
				$selabc = "";

				if ($i > 0) $teksti = t("ja paremmat");
				if ($org_rajaus == "{$i}##TM") $selabc = "SELECTED";

				echo "<option  value='$i##TM' $selabc>".t("Myynti").": {$ryhmanimet[$i]} $teksti</option>";
			}

			$teksti = "";
			for ($i=0; $i < count($ryhmaprossat); $i++) {
				$selabc = "";

				if ($i > 0) $teksti = t("ja paremmat");
				if ($org_rajaus == "{$i}##TK") $selabc = "SELECTED";

				echo "<option  value='$i##TK' $selabc>".t("Myyntikate").": {$ryhmanimet[$i]} $teksti</option>";
			}

			$teksti = "";
			for ($i=0; $i < count($ryhmaprossat); $i++) {
				$selabc = "";

				if ($i > 0) $teksti = t("ja paremmat");
				if ($org_rajaus == "{$i}##TR") $selabc = "SELECTED";

				echo "<option  value='$i##TR' $selabc>".t("Myyntirivit").": {$ryhmanimet[$i]} $teksti</option>";
			}

			$teksti = "";
			for ($i=0; $i < count($ryhmaprossat); $i++) {
				$selabc = "";

				if ($i > 0) $teksti = t("ja paremmat");
				if ($org_rajaus == "{$i}##TP") $selabc = "SELECTED";

				echo "<option  value='$i##TP' $selabc>".t("Myyntikappaleet").": {$ryhmanimet[$i]} $teksti</option>";
			}

			echo "</select>";

			echo "<tr><td colspan='2' class='back'><br></td></tr>";
			echo "<tr><td colspan='2' class='back'>".t("Valitse jos haluat tulostaa asiakaan myynnit").":</td></tr>";

			echo "<tr><th>".t("Asiakasosasto")."</th><td>";

			$query = "	SELECT distinct osasto
						FROM asiakas
						WHERE yhtio='$kukarow[yhtio]' and osasto!=''
						order by osasto+0";
			$sresult = pupe_query($query);

			echo "<select name='asiakasosasto'>";
			echo "<option value=''>".t("N�yt� kaikki")."</option>";

			while ($srow = mysql_fetch_assoc($sresult)) {
				$sel = '';
				if ($asiakasosasto == $srow["osasto"]) {
					$sel = "selected";
				}
				echo "<option value='$srow[osasto]' $sel>$srow[osasto]</option>";
			}
			echo "</select>";


			echo "	</td></tr>
					<tr><th>".t("Asiakas")."</th><td><input type='text' size='20' name='asiakasno' value='$asiakasno'></td></tr>";


			// Maanter�special
			echo "<tr><td colspan='2' class='back'><br></td></tr>";
			echo "<tr><td colspan='2' class='back'>".t("Valitse jos haluat tulostaa kaikki JT-rivit").":</td></tr>";
			echo "<tr><th>".t("N�yt� kaikki JT rivit")."</th><td><input type='checkbox' name='KAIKKIJT' value='KAIKKIJT'></td></tr>";

			echo "	</table><br>
					<input type='Submit' name='jatka' value = '".t("Jatka")."'>
					</form>";

		}

		if ($tee == "JATKA" or $tee == "RAPORTOI") {
			if ($ostoehdotus == "1")
				$kl = "CHECKED";
			if ($ostoehdotus == "2")
				$k2 = "CHECKED";
			if ($ostoehdotus == "3")
				$k3 = "CHECKED";
			if ($ostoehdotus == "4")
				$k4 = "CHECKED";

			if (!isset($ostoehdotus))
				$k3 = "CHECKED";

			if ($tuoryh != '') {
				// tehd��n avainsana query
				$sresult = t_avainsana("TRY", "", "and avainsana.selite ='$tuoryh'");
				$srow = mysql_fetch_assoc($sresult);
			}
			if ($osasto != '') {
				// tehd��n avainsana query
				$sresult = t_avainsana("OSASTO", "", "and avainsana.selite ='$osasto'");
				$trow = mysql_fetch_assoc($sresult);
			}
			if ($toimittajaid != '') {
				$query = "	SELECT nimi
							FROM toimi
							WHERE yhtio='$kukarow[yhtio]' and tunnus='$toimittajaid'";
				$sresult = pupe_query($query);
				$trow1 = mysql_fetch_assoc($sresult);
			}
			if ($asiakasid != '') {
				$query = "	SELECT nimi
							FROM asiakas
							WHERE yhtio='$kukarow[yhtio]' and tunnus='$asiakasid'";
				$sresult = pupe_query($query);
				$trow2 = mysql_fetch_assoc($sresult);
			}

			if ($rappari != $edrappari) {
				unset($valitut);
				$tee = "JATKA";
			}

			if (!isset($edrappari) or ($rappari == "" and $edrappari != "")) {
				$defaultit = "P��LLE";
			}

			$abcnimi = $ryhmanimet[$abcrajaus];

			echo "	<form method='post' autocomplete='off'>
					<input type='hidden' name='tee' value='RAPORTOI'>
					<input type='hidden' name='osasto' value='$osasto'>
					<input type='hidden' name='tuoryh' value='$tuoryh'>
					<input type='hidden' name='ytunnus' value='$ytunnus'>
					<input type='hidden' name='edrappari' value='$rappari'>
					<input type='hidden' name='toimittajaid' value='$toimittajaid'>
					<input type='hidden' name='asiakasid' value='$asiakasid'>
					<input type='hidden' name='tuotemerkki' value='$tuotemerkki'>
					<input type='hidden' name='asiakasno' value='$asiakasno'>
					<input type='hidden' name='asiakasosasto' value='$asiakasosasto'>
					<input type='hidden' name='abcrajaus' value='$abcrajaus'>
					<input type='hidden' name='abcrajaustapa' value='$abcrajaustapa'>
					<input type='hidden' name='KAIKKIJT' value='$KAIKKIJT'>

					<table>
					<tr><th>".t("Osasto")."</th><td colspan='3'>$osasto $trow[selitetark]</td></tr>
					<tr><th>".t("Tuoteryhm�")."</th><td colspan='3'>$tuoryh $srow[selitetark]</td></tr>
					<tr><th>".t("Toimittaja")."</th><td colspan='3'>$ytunnus $trow1[nimi]</td></tr>
					<tr><th>".t("Tuotemerkki")."</th><td colspan='3'>$tuotemerkki</td></tr>
					<tr><th>".t("ABC-rajaus")."</th><td colspan='3'>$abcnimi</td></tr>
					<tr><th>".t("Asiakasosasto")."</th><td colspan='3'>$asiakasosasto</td></tr>
					<tr><th>".t("Asiakas")."</th><td colspan='3'>$asiakasno $trow2[nimi]</td></tr>
					<tr><th>".t("JT")."</th><td colspan='3'>$KAIKKIJT</td></tr>";

			echo "	<tr><td class='back'><br></td></tr>";

			echo "<tr>";
			echo "<td class='back'></td>";
			echo "<th colspan='3'>",t("Alkup�iv�m��r� (pp-kk-vvvv)"),"</th>";
			echo "<td class='back'></td>";
			echo "<th colspan='3'>",t("Loppup�iv�m��r� (pp-kk-vvvv)"),"</th>";
			echo "</tr>";

			for ($i = 1; $i < 13; $i++) {
				echo "<tr>";
				echo "<th>",t("Kuukausi")," {$i}</th>";
				echo "<td><input type='text' name='ppa{$i}' value='",${"ppa{$i}"},"' size='5' /></td>";
				echo "<td><input type='text' name='kka{$i}' value='",${"kka{$i}"},"' size='5' /></td>";
				echo "<td><input type='text' name='vva{$i}' value='",${"vva{$i}"},"' size='5' /></td>";

				echo "<td class='back'> - </td>";

				echo "<td><input type='text' name='ppl{$i}' value='",${"ppl{$i}"},"' size='5' /></td>";
				echo "<td><input type='text' name='kkl{$i}' value='",${"kkl{$i}"},"' size='5' /></td>";
				echo "<td><input type='text' name='vvl{$i}' value='",${"vvl{$i}"},"' size='5' /></td>";
				echo "</tr>";
			}

			$query = "	SELECT selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji	= 'KKOSTOT'
						and selite	= '$rappari'
						and selitetark = 'TALLENNAPAIVAM'";
			$sresult = pupe_query($query);
			$srow = mysql_fetch_assoc($sresult);

			$chk = "";
			if (($srow["selitetark"] == "TALLENNAPAIVAM" and $tee == "JATKA") or $valitut["TALLENNAPAIVAM"] != '') {
				$chk = "CHECKED";
			}

			echo "<tr><th>".t("Tallenna p�iv�m��r�t:")."</th><td colspan='7'><input type='checkbox' name='valitut[TALLENNAPAIVAM]' value='TALLENNAPAIVAM' $chk></td></tr>";
			echo "	<tr><td class='back'><br></td></tr>";

			//Yhti�valinnat
			$query	= "	SELECT distinct yhtio, nimi
						from yhtio
						where konserni = '$yhtiorow[konserni]' and konserni != ''";
			$presult = pupe_query($query);

			$yhtiot 	= "";
			$konsyhtiot = "";
			$vlask 		= 0;

			if (mysql_num_rows($presult) > 0) {
				while ($prow = mysql_fetch_assoc($presult)) {

					$query = "	SELECT selitetark
								FROM avainsana
								WHERE yhtio = '$kukarow[yhtio]'
								and laji	= 'KKOSTOT'
								and selite	= '$rappari'
								and selitetark = 'YHTIO##$prow[yhtio]'";
					$sresult = pupe_query($query);
					$srow = mysql_fetch_assoc($sresult);

					$chk = "";
					if (("YHTIO##".$prow["yhtio"] == $srow["selitetark"] and $tee == "JATKA") or $valitut["YHTIO##$prow[yhtio]"] != '' or $prow["yhtio"] == $kukarow["yhtio"]) {
						$chk = "CHECKED";
						$yhtiot .= "'".$prow["yhtio"]."',";
					}

					if ($vlask == 0) {
						echo "<tr><th rowspan='".mysql_num_rows($presult)."'>Huomioi yhti�n myynnit:</th>";
					}
					else {
						echo "<tr>";
					}

					echo "<td colspan='3'><input type='checkbox' name='valitut[YHTIO##$prow[yhtio]]' value='YHTIO##$prow[yhtio]' $chk onClick='submit();'> $prow[nimi]</td></tr>";

					$konsyhtiot .= "'".$prow["yhtio"]."',";
					$vlask++;
				}

				$yhtiot = substr($yhtiot,0,-1);
				$konsyhtiot = substr($konsyhtiot,0,-1);

				echo "	<tr><td class='back'><br></td></tr>";
			}
			else {
				$yhtiot = "'".$kukarow['yhtio']."'";
				$konsyhtiot = "'".$kukarow['yhtio']."'";
			}

			//Ajetaanko varastopaikoittain
			$query = "	SELECT selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji = 'KKOSTOT'
						and selite	= '$rappari'
						and selitetark = 'PAIKOITTAIN'";
			$sresult = pupe_query($query);
			$srow = mysql_fetch_assoc($sresult);

			$chk = "";
			if (($srow["selitetark"] == "PAIKOITTAIN" and $tee == "JATKA") or $valitut["paikoittain"] != '') {
				$chk = "CHECKED";
			}

			echo "<tr><th>".t("Aja raportti varastopaikoittain")."</th><td colspan='3'><input type='checkbox' name='valitut[paikoittain]' value='PAIKOITTAIN' $chk></td></tr>";


			//N�ytet��nk� poistetut tuotteet
			$query = "	SELECT selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji = 'KKOSTOT'
						and selite	= '$rappari'
						and selitetark = 'POISTETUT'";
			$sresult = pupe_query($query);
			$srow = mysql_fetch_assoc($sresult);

			$chk = "";
			if (($srow["selitetark"] == "POISTETUT" and $tee == "JATKA") or $valitut["poistetut"] != '' or $defaultit == "P��LLE") {
				$chk = "CHECKED";
			}

			echo "<tr><th>".t("�l� n�yt� poistettuja tuotteita")."</th><td colspan='3'><input type='checkbox' name='valitut[poistetut]' value='POISTETUT' $chk></td></tr>";

			//N�ytet��nk� poistetut tuotteet
			$query = "	SELECT selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji = 'KKOSTOT'
						and selite	= '$rappari'
						and selitetark = 'POISTUVAT'";
			$sresult = pupe_query($query);
			$srow = mysql_fetch_assoc($sresult);

			$chk = "";
			if (($srow["selitetark"] == "POISTUVAT" and $tee == "JATKA") or $valitut["poistuvat"] != '' or $defaultit == "P��LLE") {
				$chk = "CHECKED";
			}

			echo "<tr><th>".t("�l� n�yt� poistuvia tuotteita")."</th><td colspan='3'><input type='checkbox' name='valitut[poistuvat]' value='POISTUVAT' $chk></td></tr>";

			//N�ytet��nk� ostoehdottamattomat tuotteet
			$query = "	SELECT selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji = 'KKOSTOT'
						and selite	= '$rappari'
						and selitetark = 'EI_OSTOEHD'";
			$sresult = pupe_query($query);
			$srow = mysql_fetch_assoc($sresult);

			$chk = "";
			if (($srow["selitetark"] == "EI_OSTOEHD" and $tee == "JATKA") or $valitut["ei_ostoehd"] != '' or $defaultit == "P��LLE") {
				$chk = "CHECKED";
			}

			echo "<tr><th>".t("�l� n�yt� ostoehdotukseen kuulumattomia tuotteita")."</th><td colspan='3'><input type='checkbox' name='valitut[ei_ostoehd]' value='EI_OSTOEHD' $chk></td></tr>";

			//N�ytet��nk� ei hinnastoon tuotteet
			$query = "	SELECT selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji = 'KKOSTOT'
						and selite	= '$rappari'
						and selitetark = 'EIHINNASTOON'";
			$sresult = pupe_query($query);
			$srow = mysql_fetch_assoc($sresult);

			$chk = "";
			if (($srow["selitetark"] == "EIHINNASTOON" and $tee == "JATKA") or $valitut["EIHINNASTOON"] != '' or $defaultit == "P��LLE") {
				$chk = "CHECKED";
			}

			echo "<tr><th>".t("�l� n�yt� tuotteita joita ei n�ytet� hinnastossa")."</th><td colspan='3'><input type='checkbox' name='valitut[EIHINNASTOON]' value='EIHINNASTOON' $chk></td></tr>";

			//N�ytet��nk� ei varastoitavat tuotteet
			$query = "	SELECT selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji = 'KKOSTOT'
						and selite	= '$rappari'
						and selitetark = 'EIVARASTOITAVA'";
			$sresult = pupe_query($query);
			$srow = mysql_fetch_assoc($sresult);

			$chk = "";
			if (($srow["selitetark"] == "EIVARASTOITAVA" and $tee == "JATKA") or $valitut["EIVARASTOITAVA"] != '') {
				$chk = "CHECKED";
			}

			echo "<tr><th>".t("�l� n�yt� tuotteita joita ei varastoida")."</th><td colspan='3'><input type='checkbox' name='valitut[EIVARASTOITAVA]' value='EIVARASTOITAVA' $chk></td></tr>";

			//N�ytet��nk� poistuvat tuotteet
			$query = "	SELECT selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji = 'KKOSTOT'
						and selite	= '$rappari'
						and selitetark = 'EHDOTETTAVAT'";
			$sresult = pupe_query($query);
			$srow = mysql_fetch_assoc($sresult);

			$chk = "";
			if (($srow["selitetark"] == "EHDOTETTAVAT" and $tee == "JATKA") or $valitut["EHDOTETTAVAT"] != '') {
				$chk = "CHECKED";
			}

			echo "<tr><th>".t("N�yt� vain ostettavaksi ehdotettavat rivit")."</th><td colspan='3'><input type='checkbox' name='valitut[EHDOTETTAVAT]' value='EHDOTETTAVAT' $chk></td></tr>";


			//N�ytet��nk� ostot varastoittain
			$query = "	SELECT selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji = 'KKOSTOT'
						and selite	= '$rappari'
						and selitetark = 'OSTOTVARASTOITTAIN'";
			$sresult = pupe_query($query);
			$srow = mysql_fetch_assoc($sresult);

			$chk = "";
			if (($srow["selitetark"] == "OSTOTVARASTOITTAIN" and $tee == "JATKA") or $valitut["OSTOTVARASTOITTAIN"] != '') {
				$chk = "CHECKED";
			}
			echo "<tr><th>".t("N�yt� tilatut varastoittain")."</th><td colspan='3'><input type='checkbox' name='valitut[OSTOTVARASTOITTAIN]' $chk></td></tr>";

			if ($abcrajaus != "") {

				echo "<tr><td class='back'><br></td></tr>";
				echo "<tr><th colspan='4'>".t("ABC-rajaus")." $ryhmanimet[$abcrajaus]</th></tr>";

				//n�ytet��nk� uudet tuotteet
				$query = "	SELECT selitetark
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							and laji = 'KKOSTOT'
							and selite	= '$rappari'
							and selitetark = 'UUDETTUOTTEET'";
				$sresult = pupe_query($query);
				$srow = mysql_fetch_assoc($sresult);

				$chk = "";
				if (($srow["selitetark"] == "UUDETTUOTTEET" and $tee == "JATKA") or $valitut["UUDETTUOTTEET"] != '') {
					$chk = "CHECKED";
				}

				echo "<tr><th>".t("�l� listaa 12kk sis�ll� perustettuja tuotteita")."</th><td colspan='3'><input type='checkbox' name='valitut[UUDETTUOTTEET]' value='UUDETTUOTTEET' $chk></td></tr>";

				//n�ytet��nk� uudet tuotteet
				$query = "	SELECT selitetark
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							and laji = 'KKOSTOT'
							and selite	= '$rappari'
							and selitetark = 'VAINUUDETTUOTTEET'";
				$sresult = pupe_query($query);
				$srow = mysql_fetch_assoc($sresult);

				$chk = "";
				if (($srow["selitetark"] == "VAINUUDETTUOTTEET" and $tee == "JATKA") or $valitut["VAINUUDETTUOTTEET"] != '') {
					$chk = "CHECKED";
				}

				echo "<tr><th>".t("Listaa vain 12kk sis�ll� perustetut tuotteet")."</th><td colspan='3'><input type='checkbox' name='valitut[VAINUUDETTUOTTEET]' value='VAINUUDETTUOTTEET' $chk></td></tr>";
			}

			echo "<tr><td class='back'><br></td></tr>";


			//Valitaan varastot joiden saldot huomioidaan
			//Tutkitaan onko k�ytt�j� klikannut useampaa yhti�t�
			if ($konsyhtiot  != '') {
				$konsyhtiot = " yhtio in (".$konsyhtiot.") ";
			}
			else {
				$konsyhtiot = " yhtio = '$kukarow[yhtio]' ";
			}

			$query = "	SELECT *
						FROM varastopaikat
						WHERE $konsyhtiot
						ORDER BY yhtio, tyyppi, nimitys";
			$vtresult = pupe_query($query);

			$vlask = 0;

			while ($vrow = mysql_fetch_assoc($vtresult)) {
				$query = "	SELECT selitetark
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							and laji = 'KKOSTOT'
							and selite	= '$rappari'
							and selitetark = 'VARASTO##$vrow[tunnus]'";
				$sresult = pupe_query($query);
				$srow = mysql_fetch_assoc($sresult);

				$chk = "";
				if (("VARASTO##".$vrow["tunnus"] == $srow["selitetark"]  and $tee == "JATKA") or $valitut["VARASTO##$vrow[tunnus]"] != '' or ($defaultit == "P��LLE" and $vrow["yhtio"] == $kukarow["yhtio"])) {
					$chk = "CHECKED";
				}

				if ($vlask == 0) {
					echo "<tr><th rowspan='".mysql_num_rows($vtresult)."'>".t("Huomioi saldot varastossa:")."</th>";
				}
				else {
					echo "<tr>";
				}

				echo "<td colspan='3'><input type='checkbox' name='valitut[VARASTO##$vrow[tunnus]]' value='VARASTO##$vrow[tunnus]' $chk> $vrow[nimitys] ($vrow[yhtio])</td></tr>";

				$vlask++;
			}


			echo "</table><br><br>";
			echo "<table>";
			echo "<tr><th colspan='4'>".t("Omat h�lytysraportit")."</th></tr>";
			echo "<tr><th>".t("Luo uusi oma raportti").":</th><td colspan='3'><input type='text' size='40' name='uusirappari' value=''></td></tr>";
			echo "<tr><th>".t("Valitse raportti").":</th><td colspan='3'>";

			// Haetaan tallennetut h�lyrapit
			$query = "	SELECT distinct selite, concat('(',replace(selite, '##',') ')) nimi
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji = 'KKOSTOT'
						ORDER BY selite";
			$sresult = pupe_query($query);

			echo "<select name='rappari' onchange='submit()'>";
			echo "<option value=''>".t("N�yt� kaikki")."</option>";

			while ($srow = mysql_fetch_assoc($sresult)) {

				$sel = '';
				if ($rappari == $srow["selite"]) {
					$sel = "selected";
				}

				echo "<option value='$srow[selite]' $sel>$srow[nimi]</option>";
			}
			echo "</select>";

			echo " <span style='float:right;'>";

			$chk = ($rappari == "" and !isset($ruksaa_kaikki)) ? "checked" : "";

			echo t("Ruksaa kaikki")," <input type='checkbox' name='ruksaa_kaikki' id='ruksaa_kaikki' {$chk} />";

			echo "</span>";

			echo "</td></tr>";

			$lask = 0;
			echo "<tr>";

			foreach($sarakkeet as $key => $sarake) {

				$query = "	SELECT selitetark
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							and laji	= 'KKOSTOT'
							and selite	= '$rappari'
							and selitetark = '$key'";
				$sresult = pupe_query($query);

				$sel = "";
				if (mysql_num_rows($sresult) == 1 or $rappari == "") {
					$sel = "CHECKED";
				}

				if ($lask % 4 == 0 and $lask != 0) {
					echo "</tr><tr>";
				}

				echo "<td><input class='valitut_checkboxit' type='checkbox' name='valitut[$key]' value='$key' $sel>".ucfirst($sarake)."</td>";
				$lask++;
			}

			echo "</tr>";
			echo "</table>";
			echo "<br>
				<input type='Submit' name='RAPORTOI' value = '".t("Aja kuukautisraportti")."'>
				</form>";
		}

		require ("inc/footer.inc");
	}