#!/bin/sh
# Usage: start-clean.sh PROCESS_NAME COMMAND [ARGS...]
#
# Runs COMMAND in the foreground, after stopping any leftover PROCESS_NAME processes.
# If a master process is killed hard (for example by the out-of-memory killer), its workers
# survive and keep the port open, so a restarted master would fail with "address already in use".
# On a normal start there is nothing to clear.
name=$1
shift

if pkill -x "$name" 2>/dev/null; then
    sleep 1
fi

exec "$@"
