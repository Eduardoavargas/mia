<?php

namespace App\Conversations;

use App\Person;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Conversations\Conversation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CoreConversation extends Conversation
{
    protected $name;
    protected $age;
    protected $cep;
    protected $cpf;
    protected $phone;
    protected $acceptedTerms;
    protected $firstConversation = false;


    private function getPersonByPhone()
    {
        $phone = $this->getFormattedPhone();
        $this->phone = $phone;
        $phoneNewPattern = substr($phone, 0, 2) . '9' . substr($phone, 2);
        $person = Person::where('phone', $phone)->orWhere('phone', $phoneNewPattern)->first();

        if (!$person) {
            $person = new Person([
                'phone' => $phone,
            ]);
            $person->save();
            $this->firstConversation = true;
        }

        $this->personName = $person->name ?? null;
        $this->name = $person->name ?? null;
        $this->cep = $person->cep ?? null;
        $this->cpf = $person->cpf ?? null;
        $this->age = $person->birthday ?? null;

        return $person;
    }

    private function getFormattedPhone()
    {
        $phone = str_replace('@c.us', '', $this->bot->getUser()->getId());

        return $phone;
    }

    public function askInitialContact()
    {
        $message = 'Olá, sou o *MIA* a sua assistente pessoal.
Você vai poder contar comigo em todas as situações, exemplo:
*INFORMAÇÕES SOBRE TRÁFEGO* 🚧
*LUGARES SEGUROS PARA PARADA* ✔
*AUXILIO A SAUDE* 👩‍🔬
*PEDIR SOCOROO* 🆘
*e muito mais*.

Antes de começarmos preciso realizar seu cadastro, é bem simples.
*Podemos fazer agora?*
Responda *sim* ou *não*';
        $this->say($message, function (Answer $answer) {
            switch ($answer->getText()) {
                case 'sim':
                case 'Sim':
                case 'SIM':
                case '👍':
                    {
                        $this->askName();
                    }
                case 'nao':
                case 'não':
                case 'Não':
                case 'NÂO':
                case '👎':
                    {
                        $this->say('Ok, me chame quando estiver disponível.');
                    }
                default:
                    {
                        $this->sayWrongAnswer();
                    }
            }
        });
    }

    public function saveFirstConversation(Person $person)
    {
        $person->first_conversation = Carbon::now();
        $person->save();
    }

    public function askName()
    {
        $question = 'Qual seu nome?';

        $this->ask($question, function (Answer $answer) {
            $this->name = $answer->getText();
            Person::query()->where('phone', '=', $this->phone)->update(['name' => $answer->getText()]);
            $this->askAge();
        });
    }

    public function askAge()
    {
        $question = 'Qual sua data de nascimento?  Responda no formato 00/00/0000';
        $this->ask($question, function (Answer $answer) {

            if (preg_match("/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/", $answer->getText()) == false) {
                $this->sayWrongAnswer();
                return $this->askAge();
            }
            $this->age = $answer->getText();
            Person::query()->where('phone', '=', $this->phone)->update(['birthday' => $answer->getText()]);
            $this->askCEP();
        });
    }

    public function askCEP()
    {
        $question = $this->name . ' Qual seu CEP? Responda no formato 88888-888';

        $this->ask($question, function (Answer $answer) {

            if (preg_match("/^[0-9]{5}-[0-9]{3}$/", $answer->getText()) == false) {
                $this->sayWrongAnswer();
                return $this->askCEP();
            }
            $this->cep = $answer->getText();
            Person::query()->where('phone', '=', $this->phone)->update(['cep' => $answer->getText()]);
            $this->askCPF();
        });
    }

    public function askCPF()
    {
        $question = 'Qual seu CPF? Responda no formato 888.888.888-88';

        $this->ask($question, function (Answer $answer) {

            if (preg_match("/^[0-9]{3}.[0-9]{3}.[0-9]{3}-[0-9]{2}$/", $answer->getText()) == false) {
                $this->sayWrongAnswer();
                return $this->askCPF();
            }
            $this->cpf = $answer->getText();
            Person::query()->where('phone', '=', $this->phone)->update(['cpf' => $answer->getText()]);
            $this->say("Perfeito, por te se cadastrado você ja ganhou 🏆 *100 pontos*.");
            $this->say("Conforme você utiliza nossos serviços você ganhará mais pontos, atigindo 1000 pontos você ja pode trocar por varios produtos e serviços.");
            $this->say("Agora vou te passar as opções em que posso ser útil no momento.");
            $this->showMenu();
        });
    }

    public function showMenu()
    {
        $question = '*' . $this->name . '* Escolha uma das opções!
*1*  Telefones Úteis 📞☎
*2*  Informações de paradas 🚰✅
*3*  Situação de rodovias em tempo real 🛣🚧
*4*  Restaurantes 🍛
*5*  Postos de combustiveis ⛽
*6*  Pontos de saúde 🏥
*7*  SOS 🆘

Responda com o número correspondente.
(Por exemplo: *4*)';
        $this->ask($question, function (Answer $answer) {
            if (preg_match("/^\d$/", $answer->getText()) == false) {
                $this->sayWrongAnswer();
                return $this->showMenu();
            }
            switch ($answer->getText()) {
                case '1':
                    return $this->sayPhonesUtils();
                case '2':
                case '3':
                    return $this->sayTraffic();
                case '4':
                case '5':
                case '6':
                case '7':
            }
        });
    }

    public function sayTraffic()
    {
        $question = '*' . $this->name . '* Escolha uma das rodovias
Atualmente só cubrimos rodovias pedagiadas!
*1* Rodovia Presidente Dutra (CCR NovaDutra)
*2* Rodovia dos Lagos - RJ-124 (CCR ViaLagos
*3* BR-277 (CCR RodoNorte)
*4* BR-376 (CCR RodoNorte)
*5* PR-151 (CCR RodoNorte)
*6* BR-373 (CCR RodoNorte)
*7* SP-330 Anhanguera (CCR AutoBAn)
*8* SP-348 Bandeirantes (CCR AutoBAn)
*9* SP-300 Dom Gabriel Paulino Bueno Couto (CCR AutoBAn)
*10* SPI-102/330 Adalberto Panzan (CCR AutoBAn)
*11* Rodovia Castello Branco (CCR ViaOeste)
*12* Rodovia Senador José Ermírio de Moraes (CCR ViaOeste)
*13* Rodovia Raposo Tavares (CCR ViaOeste)
*14* Rodovia Dr. Celso Charuri (CCR ViaOeste)
*15* Rodoanel Mário Covas (CCR RodoAnel)
*16* SP-280 Castello Branco (CCR SPVias)
*17* SP-270 Raposo Tavares (CCR SPVias)
*18* SP-255 João Mellão (CCR SPVias)
*19* SP-258 Francisco Alves Negrão (CCR SPVias)
*20 Ver mais*

Responda com o número correspondente.
(Por exemplo: *4*)';
        $this->ask($question, function (Answer $answer) {
            if (preg_match("/^\d$/", $answer->getText()) == false) {
                $this->sayWrongAnswer();
                return $this->showMenu();
            }
            switch ($answer->getText()) {
                case '1':
                    return $this->sayTrafficNow('http://www.novadutra.com.br/generic/home/ListOccurrences');
                case '2':
                    return $this->sayTrafficNow('http://www.rodoviadoslagos.com.br/generic/home/ListOccurrences');
                case '3':
                case '4':
                case '5':
                case '6':
                return $this->sayTrafficNow('http://www.rodonorte.com.br/generic/home/ListOccurrences');
            }
        });
    }

    public function sayTrafficNow($uri)
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->get($uri);
        $response = json_decode($response->getBody());
        foreach ($response as $resp) {
            if(is_array($resp)) {
                foreach ($resp as $res) {
                    $this->say('
Rodovia: *' . $res->RoadName . '*
Situação: *' . $res->OccurrenceTypeName . '*
No km: *' . $res->DistanceMarkerIni . '* ao *' . $res->DistanceMarkerEnd . '*
Informação: *' . $res->TrafficText . '*
');
                }
            }
        }

        return $this->say('Essas são as informações no momento');
    }

    public function sayPhonesUtils()
    {
        $this->say('
*AUTOBAN CCR:* 0800-0555550
*AUTOVIAS:* 0800-7079000
*CART:* 0800-7730090
*CENTROVIAS:* 0800-178998
*COLINAS:* 0800-7035080
*ECOPISTAS:* 0800-7770070
*ECOVIAS:* 0800-197878
*ENTREVIAS:* 0800-3000333​
*INTERVIAS:* 0800-7071414
*RENOVIAS:* 0800-0559696
*RODOANEL OESTE:* 0800-7736699
*RODOVIAS DO TIETÊ:* 0800-7703322
*ROTA DAS BANDEIRAS:* 0800-7708070
*SPMAR:* 0800-7748877
*SPVIAS:* 0800-7035030
*TAMOIOS:* 0800-5450000
*TEBE:* 0800-551167
*TRIÂNGULO DO SOL:* 0800-7011609
*VIAOESTE:* 0800-7015555
*VIARONDON:* 0800-7299300
*Polícia Rodoviária Federal:* 191
*Artesp:* 0800-7278377
*DER:* 0800-0555510'
        );
    }

    public function sayWrongAnswer()
    {
        $this->say('Resposta inválida! Por favor, responda corretamente.');
    }

    public function checkPersonExists()
    {
        $person = $this->getPersonByPhone();

        if ($this->firstConversation) {
            return $this->askInitialContact();
        }

        if ($person->name === null) {
            return $this->askName();
        }

        if ($person->birthday === null) {
            return $this->askAge();
        }

        if ($person->cep === null) {
            return $this->askCEP();
        }

        if ($person->cpf === null) {
            return $this->askCPF();
        }

        return $this->showMenu();
    }

    /**
     * Start the conversation
     */
    public function run()
    {
        //$this->checkPersonExists();
    }
}
