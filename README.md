# Running using docker

Create environment file (stack.env), with following vars (see .env.example):
```
# Enable debug mode for AMP library
AMP_DEBUG=0

RELAY_MAX_CONNECTIONS_PER_IP=1000

AGENT_NSEC=<AGENT NSEC-key>
RELAY_URL=<remote accessible URL to your relay>
RELAY_OWNER_NPUB=<OWNER OR ROOT NPUB-key>
RELAY_NAME=<Your relay name>
RELAY_DESCRIPTION=<A brief description of your relay>
RELAY_CONTACT=<EMAIL TO CONTACT YOU>
```

Spin up
```
docker compose --env-file stack.env up
```

Your relay should be listening at the IP address of your docker container at port 80. You can create a reverse proxy in front of it to open it up to the public