<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Mockery\Undefined;

class FlightController extends Controller
{
    //
    public function index(){
        $response = Http::get('http://prova.123milhas.net/api/flights');
        $resultado = $response->json(); 
        //Resultados possíveis da API -> 
        // id -> Identificador único do voo 
        // cia -> Companhia aérea responsável pelo voo
        // fare -> Tipo de tarifa (Fator determinante no agrupamento)
        // flightNumber -> Número do voo
        // origin -> Identificador do aeroporto de saída
        // destination -> Identificador do aeroporto de chegada
        // departureDate -> Data de saída do voo
        // arrivalDate -> Data de chegada do voo
        // departureTime -> Data de saída do voo    
        // arrivalTime -> Hora chegada
        // classService -> Classe do serviço
        // price -> Preço do voo (Fator determinante no agrupamento)
        // tax -> Taxa
        // inbound -> Determina se o voo é ida (Fator determinante no agrupamento) 
        // outbound -> Determina se o voo é volta (Fator determinante no agrupamento)
        // duration ->

        //Array para guardar os tipos de tarifa
        $tipoTarifa = array();

        //Array para guardar os preços de ida por tarifa
        $tarifaIda = array();
        
        //Array para guardar os preços de volta por tarifa
        $tarifaVolta = array();

       
        foreach($resultado as $voos){ // Foreach percorrendo o resultado retornado pela API da 123milhas
            if(!in_array($voos['fare'], $tipoTarifa)){ // alimentando o vetor com os tipos de tarifas existentes
                $tipoTarifa[] = $voos['fare'];
                $tarifaVolta[$voos['fare']] = array(); // Vetores auxiliares para agrupamento de tarifcas
                $tarifaIda[$voos['fare']] = array();
            }

            if($voos['inbound'] == 1){ // voo de ida
                if(isset($tarifaIda[$voos['fare']]) && !in_array($voos['price'], $tarifaIda[$voos['fare']] ))
                    $tarifaIda[$voos['fare']][] = $voos['price'];
            }else if($voos['outbound'] == 1){ // voo de volta
                // $tarifaVolta[$voos['fare']][] = $voos['price'];
                if(isset($tarifaVolta[$voos['fare']]) && !in_array($voos['price'], $tarifaVolta[$voos['fare']] ))
                    $tarifaVolta[$voos['fare']][] = $voos['price'];
            }
        }
        

        // Criando grupos
        $arrayGrupos = array();
        
        $uniqueId = 0; // id unico que será retornado para cada agrupamento.

        //Preços totais
        $precoTotais = array();
        $uniqueIdMenor = 0;
        foreach($tipoTarifa as $aux){
            foreach($tarifaIda[$aux] as $ida){
                $grupoIdaArray = array();
                $grupoVoltaArray = array();
                $valorTotal = 0;
                // $grupos = "";
                // $grupos .= "<br>Grupos: Ida ->";
                
                foreach($resultado as $voos){
                
                    if($voos['price'] == $ida && $voos['fare'] == $aux && $voos['inbound'] == 1){
                        // $grupos .= $voos['id'].",";
                        $grupoIdaArray[] = $voos; 
                    }
                }
                $valorTotal = $ida;
                foreach($tarifaVolta[$aux] as $volta){
                    
                    // echo $grupos;
                    // echo " Volta -> ";
                
                    foreach($resultado as $voos){
                        if($voos['price'] == $volta && $voos['fare'] == $aux && $voos['outbound'] == 1){
                            // echo $voos['id'].",";
                            $grupoVoltaArray[] = $voos;
                        }
                    }
                    $arrayGrupos[$uniqueId]['uniqueId'] = $uniqueId;
                    $arrayGrupos[$uniqueId]['totalPrice'] = (int)($valorTotal + $volta);
                    $arrayGrupos[$uniqueId]['outbound'] = $grupoIdaArray;
                    $arrayGrupos[$uniqueId]['inbound'] = $grupoVoltaArray;

                    $precoTotais[] = (int)($valorTotal + $volta);
                    if(min($precoTotais) == (int)($valorTotal + $volta)){
                        $uniqueIdMenor = $uniqueId;
                        $menorPreco = (int)($valorTotal + $volta);
                    }
                    
                    $uniqueId++;
                }
                
            }
        }

        $resultadoFinal = array();
        $resultadoFinal['flights'] = $resultado;
        $resultadoFinal['groups'] = $arrayGrupos;
        $resultadoFinal['totalGroups'] = count($arrayGrupos);
        $resultadoFinal['totalFlights'] = count($arrayGrupos);
        $resultadoFinal['cheapestPrice'] = $menorPreco;
        $resultadoFinal['cheapestGroup'] = $uniqueIdMenor;
        
        
        echo json_encode($resultadoFinal);
        
    }
}
