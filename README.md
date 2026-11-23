# passa lá?

Digita pra onde você vai e veja todas as linhas de ônibus que passam a até 1 km de lá.
Sem rastreio em tempo real: os horários são os programados pelo Grande Recife.

Por enquanto cobre a Região Metropolitana do Recife (Pernambuco).

## Como funciona

1. O nome do lugar vira coordenada pelo [Photon](https://photon.komoot.io) (OpenStreetMap), limitado a Pernambuco.
   Se o lugar não aparece na busca, dá pra tocar no mapa: o ponto ganha nome pelo endereço.
2. O PostGIS acha as paradas atendidas no dia escolhido (dias úteis, sábado ou domingo; hoje por padrão)
   dentro do raio (300 m, 500 m ou 1 km) e, pra cada linha, a parada mais próxima. Se nenhuma linha passa
   no raio, mostra as 5 mais perto até 5 km.
3. Clicando numa linha, o mapa mostra ida e volta daquele dia, as paradas e onde descer em cada sentido.
4. Se a pessoa libera a localização, o app escolhe o sentido certo (o que passa pela parada dela antes do destino),
   mostra onde pegar e o caminho a pé até lá, pelo roteador do [FOSSGIS](https://routing.openstreetmap.de).
   Tocando em outra parada no mapa, ela vira o embarque.
5. Com o embarque escolhido, o painel mostra os próximos ônibus ali (previstos) e todos os horários do dia.
   Tocando numa segunda parada, ela vira onde descer e cada ônibus ganha a hora de chegada e o tempo de viagem.
   A última parada de um sentido nunca vira embarque.
6. Também dá pra buscar a linha direto, pelo número ou pelo nome.
7. No modo De → Para, a pessoa escolhe origem e destino (lugares salvos, busca, mapa ou a própria
   localização) e vê as linhas que levam direto de um ao outro, sem baldeação.

As linhas vêm do [GTFS do Grande Recife](https://www.granderecife.pe.gov.br/gtfs/gtfs.zip).
O comando `gtfs:sync` baixa o feed (só quando o ETag muda), importa só as linhas de ônibus com
serviço ativo e, pra cada sentido e tipo de dia, o trajeto mais usado e a ordem das paradas da viagem
principal. Guarda também todas as viagens, agrupadas por sequência de paradas (`patterns` e `pattern_stop`),
cada uma com o horário em cada parada (`runs.times`, segundos desde o início do dia de serviço, então
passa de 24h nas viagens de madrugada). Registra em `syncs` quais linhas entraram e saíram.

### Atualização semanal

Em produção quem roda o `gtfs:sync` é o GitHub Actions (`.github/workflows/gtfs.yml`), toda segunda às
04:00 do Recife, então o servidor não precisa de agendador. Pra ativar, cadastre o secret `DB_URL`
(`postgresql://usuario:senha@host:porta/banco?sslmode=require`) em Settings → Secrets and variables →
Actions. Cada execução deixa no resumo as linhas que entraram e saíram, abre uma issue quando alguma
linha muda e pode ser disparada na mão em Actions → Linhas do Grande Recife → Run workflow.

Rodando tudo na própria máquina, o container `scheduler` faz o mesmo papel.

## Rodando

Precisa de Docker, Node e [just](https://github.com/casey/just).

```sh
cp .env.example .env
just up
just install
just artisan key:generate
just migrate
just sync
just build
```

O site fica em http://localhost:7350.

| Comando | O que faz |
| --- | --- |
| `just up` / `just down` | sobe e derruba os containers |
| `just sync` | importa o GTFS agora |
| `just test` | roda os testes (Pest) |
| `just lint` | Pint + Larastan |
| `just build` | gera os assets (Vite) |

## API

Pública, sem autenticação, em `/api/v1`. É a mesma que o app mobile vai usar.

| Rota | Retorna |
| --- | --- |
| `GET /places?q=patteo` | lugares em Pernambuco que batem com o texto |
| `GET /places/reverse?lat=&lon=` | nome do lugar num ponto do mapa |
| `GET /lines?lat=-7.99&lon=-34.84&day=sunday&radius=500` | linhas no raio que rodam no dia, com a parada mais próxima e a distância; `meta.wide` indica que nada passava no raio e vieram as mais perto |
| `GET /lines/search?q=823` | linhas pelo número ou pelo nome |
| `GET /lines/{id}?day=&lat=&lon=&from_lat=&from_lon=&stop=&end=` | ida e volta do dia (GeoJSON e paradas em ordem), onde pegar e onde descer em cada sentido, o sentido recomendado e os horários no embarque (`times`) |
| `GET /rides?from_lat=&from_lon=&lat=&lon=&day=&radius=` | linhas diretas entre dois pontos, com onde pegar, onde descer, caminhada e número de paradas |
| `GET /walk?from_lat=&from_lon=&lat=&lon=` | caminho a pé (GeoJSON), metros e minutos |

`day` aceita `weekday`, `saturday` ou `sunday` e, sem ele, vale o dia de hoje no horário do Recife.
`radius` vai de 100 a 1000 metros (padrão 1000).
`lat`/`lon` é o destino, `from_lat`/`from_lon` é onde a pessoa está, `stop` é a parada que ela escolheu
pra embarcar e `end` a que escolheu pra descer. A posição da pessoa não é gravada e o nginx não loga as rotas da API.

`times` vem em cada sentido que tem embarque: `all` são os horários do dia (`HH:MM`), `next` os 3 próximos
com os minutos até cada um (`in`), só quando o dia é hoje e contando a madrugada do dia anterior, e, quando
há onde descer depois do embarque, `end` em cada ônibus e `ride`, a mediana do tempo de viagem em minutos.

## Estrutura

```
app/
  Domain/   regras puras, sem Laravel (Geo, Transit)
  Cases/    casos de uso: Lookup, Pin, Search, Find, Show, Trip, Sync, Walk
  Infra/    PostGIS, leitor GTFS, Photon, OSRM
  Http/Api/ controllers, requests e JSON
resources/js/
  finder.js    estado da tela (Alpine)
  map/         mapa (MapLibre + OpenFreeMap)
```

## Créditos

Linhas: Grande Recife Consórcio de Transporte. Mapa: [OpenFreeMap](https://openfreemap.org),
© [OpenStreetMap](https://www.openstreetmap.org/copyright). Busca: Photon (Komoot). Caminho a pé: OSRM do FOSSGIS.
