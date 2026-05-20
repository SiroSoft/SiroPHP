# Siro CLI Zsh Completion
# Source this file in your ~/.zshrc:
#   source /path/to/siro-completion.zsh

_siro_completion() {
    local -a commands

    # Cache commands for 10 seconds
    local cache_file="${TMPDIR:-/tmp}/siro-commands.cache"
    if [[ -f "$cache_file" ]] && [[ $(($(date +%s) - $(stat -f %m "$cache_file" 2>/dev/null || stat -c %Y "$cache_file" 2>/dev/null))) -lt 10 ]]; then
        commands=("${(@f)$(< "$cache_file")}")
    else
        commands=("${(@f)$(php siro list --raw 2>/dev/null)}")
        printf '%s\n' "${commands[@]}" > "$cache_file" 2>/dev/null
    fi

    _describe 'siro commands' commands
}

compdef _siro_completion siro
