FROM --platform=$BUILDPLATFORM golang:1.23 AS builder

WORKDIR /build

COPY go.mod go.sum ./
RUN go mod download

COPY . .

ARG TARGETARCH
ARG TARGETOS
ENV GOOS=${GOOS}
ENV GOARCH=${GOARCH}
ENV CGO_ENABLED=0

RUN --mount=type=cache,target=/root/.cache/go-build go build -o echoserver .

ARG UID=10000
ARG GID=10000
RUN groupadd -g ${GID} echoserver && useradd -u ${UID} -g ${GID} -m echoserver


FROM scratch

COPY --from=builder /etc/passwd /etc/passwd
COPY --from=builder /etc/group /etc/group

COPY --from=builder /build/echoserver /echoserver

USER echoserver

ENTRYPOINT ["/echoserver"]
