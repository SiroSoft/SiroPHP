{{- define "siro-api.name" -}}
{{- default .Chart.Name .Values.nameOverride | trunc 63 | trimSuffix "-" }}
{{- end }}

{{- define "siro-api.fullname" -}}
{{- if .Values.fullnameOverride }}
{{- .Values.fullnameOverride | trunc 63 | trimSuffix "-" }}
{{- else }}
{{- $name := default .Chart.Name .Values.nameOverride }}
{{- if contains $name .Release.Name }}
{{- .Release.Name | trunc 63 | trimSuffix "-" }}
{{- else }}
{{- printf "%s-%s" .Release.Name $name | trunc 63 | trimSuffix "-" }}
{{- end }}
{{- end }}
{{- end }}

{{- define "siro-api.labels" -}}
helm.sh/chart: {{ include "siro-api.name" . }}-{{ .Chart.Version | replace "+" "_" }}
{{ include "siro-api.selectorLabels" . }}
{{- if .Chart.AppVersion }}
app.kubernetes.io/version: {{ .Chart.AppVersion | quote }}
{{- end }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
{{- end }}

{{- define "siro-api.selectorLabels" -}}
app.kubernetes.io/name: {{ include "siro-api.name" . }}
app.kubernetes.io/instance: {{ .Release.Name }}
{{- end }}

{{- define "siro-api.jwtSecret" -}}
{{- if .Values.jwt.secret }}
{{- .Values.jwt.secret }}
{{- else }}
{{- randAlphaNum 48 }}
{{- end }}
{{- end }}
