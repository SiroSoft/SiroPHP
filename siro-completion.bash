# Siro CLI Bash Completion
# Source this file in your ~/.bashrc or ~/.bash_profile:
#   source /path/to/siro-completion.bash

_siro_completion() {
    local cur="${COMP_WORDS[COMP_CWORD]}"
    local prev="${COMP_WORDS[COMP_CWORD-1]}"

    # If -- is typed, suggest options
    if [[ "$cur" == --* ]]; then
        local opts="--help --version --force --simple --seed --with-swagger --daemon --coverage --dry-run --edit --diff --https --insecure --json"
        COMPREPLY=($(compgen -W "$opts" -- "$cur"))
        return 0
    fi

    # List commands (cache for 10s)
    local commands
    local cache_file="/tmp/siro-commands.cache"
    if [[ -f "$cache_file" ]] && [[ $(($(date +%s) - $(stat -f %m "$cache_file" 2>/dev/null || stat -c %Y "$cache_file" 2>/dev/null))) -lt 10 ]]; then
        commands=$(cat "$cache_file")
    else
        commands=$(php siro list --raw 2>/dev/null)
        echo "$commands" > "$cache_file" 2>/dev/null
    fi

    COMPREPLY=($(compgen -W "$commands" -- "$cur"))
}

complete -F _siro_completion siro
