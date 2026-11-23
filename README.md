# passa lá?

Digita pra onde você vai e veja todas as linhas de ônibus que passam a até 1 km de lá.
Sem horário, sem rastreio em tempo real: só quais ônibus passam perto.

Por enquanto cobre a Região Metropolitana do Recife (Pernambuco).

## Como funciona

1. O nome do lugar vira coordenada pelo [Photon](https://photon.komoot.io) (OpenStreetMap), limitado a Pernambuco.
2. O PostGIS acha as paradas a até 1 km atendidas no dia escolhido (dias úteis, sábado ou domingo; hoje por padrão)
   e, pra cada linha, a parada mais próxima.
3. Clicando numa linha, o mapa mostra ida e volta daquele dia, as paradas e onde descer em cada sentido.
4. Se a pessoa libera a localização, o app escolhe o sentido certo (o que passa pela parada dela antes do destino),
   mostra onde pegar e o caminho a pé até lá, pelo roteador do [FOSSGIS](https://routing.openstreetmap.de).
   Tocando em outra parada no mapa, ela vira o embarque.

As linhas vêm do [GTFS do Grande Recife](https://www.granderecife.pe.gov.br/gtfs/gtfs.zip).
O comando `gtfs:sync` baixa o feed (só quando o ETag muda), importa só as linhas de ônibus com
serviço ativo e, pra cada sentido e tipo de dia, o trajeto mais usado e a ordem das paradas da viagem
principal. Registra em `syncs` quais linhas entraram e saíram. Ele roda toda segunda às 04:00.

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
| `GET /lines?lat=-7.99&lon=-34.84&day=sunday` | linhas a até 1 km que rodam no dia, com a parada mais próxima e a distância |
| `GET /lines/{id}?day=&lat=&lon=&from_lat=&from_lon=&stop=` | ida e volta do dia (GeoJSON e paradas em ordem), onde pegar e onde descer em cada sentido e o sentido recomendado |
| `GET /walk?from_lat=&from_lon=&lat=&lon=` | caminho a pé (GeoJSON), metros e minutos |

`day` aceita `weekday`, `saturday` ou `sunday` e, sem ele, vale o dia de hoje no horário do Recife.
`lat`/`lon` é o destino, `from_lat`/`from_lon` é onde a pessoa está e `stop` é a parada que ela escolheu
pra embarcar. A posição da pessoa não é gravada e o nginx não loga as rotas da API.

## Estrutura

```
app/
  Domain/   regras puras, sem Laravel (Geo, Transit)
  Cases/    casos de uso: Lookup, Search, Show, Sync, Walk
  Infra/    PostGIS, leitor GTFS, Photon, OSRM
  Http/Api/ controllers, requests e JSON
resources/js/
  finder.js    estado da tela (Alpine)
  map/         mapa (MapLibre + OpenFreeMap)
```

## Créditos

Linhas: Grande Recife Consórcio de Transporte. Mapa: [OpenFreeMap](https://openfreemap.org),
© [OpenStreetMap](https://www.openstreetmap.org/copyright). Busca: Photon (Komoot). Caminho a pé: OSRM do FOSSGIS.
