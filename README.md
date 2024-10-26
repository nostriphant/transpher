# Running using docker

Create environment file (stack.env), with following variables (see .env.example):
```
# Enable debug mode for AMP library
AMP_DEBUG=0

RELAY_MAX_CONNECTIONS_PER_IP=1000

AGENT_NSEC=<AGENT NSEC-key>
RELAY_URL=<remote accessible URL starting with ws:// or wss:// to your relay, only used in agent>
RELAY_OWNER_NPUB=<OWNER OR ROOT NPUB-key>
RELAY_NAME=<Your relay name>
RELAY_DESCRIPTION=<A brief description of your relay>
RELAY_CONTACT=<EMAIL TO CONTACT YOU>
RELAY_PORT=2121
```

Spin up
```
docker compose up
```

Your relay should be listening at the IP address of your docker container at port 80. You can create a reverse proxy in front of it to open it up to the public

## Run the relay locally

Let's say you want to run the relay locally for development purposes. 
Copy and paste the `.env.example` file to `.env` and set all variables in this file.
Make sure to set this variable there `RELAY_URL=ws://10.8.0.8` so the agent container can connect to the relay container.
Now you can spin up the Docker containers.

```bash
docker compose -f docker-compose.local.yml up
```

To connect to the relay, you can use `ws://127.0.0.1:<RELAY_PORT>` as a URI.