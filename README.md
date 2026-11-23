# passa lá?

Digita pra onde você vai e veja todas as linhas de ônibus que passam a até 1 km de lá.
Sem horário, sem rastreio em tempo real: só quais ônibus passam perto.

Por enquanto cobre a Região Metropolitana do Recife (Pernambuco).

## Como funciona

1. O nome do lugar vira coordenada pelo [Photon](https://photon.komoot.io) (OpenStreetMap), limitado a Pernambuco.
2. O PostGIS acha as paradas a até 1 km e, pra cada linha, a parada mais próxima.
3. Clicando numa linha, o mapa mostra o trajeto completo (ida e volta), as paradas e onde pegar.

As linhas vêm do [GTFS do Grande Recife](https://www.granderecife.pe.gov.br/gtfs/gtfs.zip).
O comando `gtfs:sync` baixa o feed (só quando o ETag muda), importa só as linhas de ônibus com
serviço ativo e registra em `syncs` quais linhas entraram e saíram. Ele roda toda segunda às 04:00.

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
| `GET /lines?lat=-7.99&lon=-34.84` | linhas a até 1 km, com a parada mais próxima e a distância |
| `GET /lines/{id}?lat=&lon=` | trajeto (GeoJSON), paradas e onde pegar a linha |

## Estrutura

```
app/
  Domain/   regras puras, sem Laravel (Geo, Transit)
  Cases/    casos de uso: Lookup, Search, Show, Sync
  Infra/    PostGIS, leitor GTFS, Photon
  Http/Api/ controllers, requests e JSON
resources/js/
  finder.js    estado da tela (Alpine)
  map/         mapa (MapLibre + OpenFreeMap)
```

## Créditos

Linhas: Grande Recife Consórcio de Transporte. Mapa: [OpenFreeMap](https://openfreemap.org),
© [OpenStreetMap](https://www.openstreetmap.org/copyright). Busca: Photon (Komoot).
