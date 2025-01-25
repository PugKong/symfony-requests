package main

import (
	"bytes"
	"cmp"
	"encoding/json"
	"encoding/xml"
	"fmt"
	"log"
	"net/http"
	"os"
	"strconv"
	"strings"

	"github.com/clbanning/mxj/v2"
)

func main() {
	addr := cmp.Or(os.Getenv("ECHOSERVER_LISTEN"), "localhost:8000")

	http.HandleFunc("/", handler)

	log.Printf("[INFO] Listening %s", addr)
	if err := http.ListenAndServe(addr, nil); err != nil {
		log.Printf("[ERROR] Serve: %v", err)
	}
}

type response struct {
	Method  string            `json:"method"`
	Path    string            `json:"path"`
	Headers map[string]string `json:"headers,omitempty"`
	Query   map[string]string `json:"query,omitempty"`
	Body    any               `json:"body,omitempty,omitzero"`
}

func handler(w http.ResponseWriter, r *http.Request) {
	statusCode, err := parseStatusCode(r)
	if err != nil {
		writeError(http.StatusBadRequest, err, w, r)

		return
	}

	body, err := parseBody(r)
	if err != nil {
		writeError(http.StatusBadRequest, err, w, r)

		return
	}

	headers := map[string]string{}
	for key := range r.Header {
		if key == "Content-Length" || strings.HasPrefix(key, "X-") {
			continue
		}

		headers[key] = r.Header.Get(key)
	}

	query := map[string]string{}
	for key := range r.URL.Query() {
		query[key] = r.URL.Query().Get(key)
	}

	var resp any
	resp = response{
		Method:  r.Method,
		Path:    r.URL.Path,
		Headers: headers,
		Query:   query,
		Body:    body,
	}
	if r.Header.Get("X-Response-Shape") == "array" {
		resp = []any{resp}
	}

	writeResponse(statusCode, resp, w, r)
}

func parseStatusCode(r *http.Request) (int, error) {
	status := cmp.Or(r.Header.Get("X-Status-Code"), "200")
	statusCode, err := strconv.Atoi(status)
	if err != nil {
		return 0, fmt.Errorf("parse status code: %w", err)
	}

	return statusCode, nil
}

func parseBody(r *http.Request) (any, error) {
	var body any

	switch r.Header.Get("Content-Type") {
	case "application/json":
		if err := json.NewDecoder(r.Body).Decode(&body); err != nil {
			return nil, fmt.Errorf("parse json body: %w", err)
		}
	case "application/xml":
		b, err := mxj.NewMapXmlReader(r.Body)
		if err != nil {
			return nil, fmt.Errorf("parse xml: %w", err)
		}

		body = b
	case "application/x-www-form-urlencoded":
		if err := r.ParseForm(); err != nil {
			return nil, fmt.Errorf("parse form: %w", err)
		}

		b := map[string]string{}
		for key := range r.Form {
			b[key] = r.Form.Get(key)
		}
		body = b
	}

	return body, nil
}

func writeResponse(statusCode int, resp any, w http.ResponseWriter, r *http.Request) {
	accept := r.Header.Get("Accept")
	if accept != "application/json" && accept != "application/xml" {
		writeError(http.StatusBadRequest, fmt.Errorf("unsupported accept: %s", accept), w, r)

		return
	}

	w.Header().Add("Content-Type", accept)
	w.WriteHeader(statusCode)

	var encode func(any) error
	switch r.Header.Get("Accept") {
	case "application/json":
		encode = json.NewEncoder(w).Encode
	case "application/xml":
		encode = func(v any) error {
			buf := bytes.Buffer{}
			if err := json.NewEncoder(&buf).Encode(resp); err != nil {
				return fmt.Errorf("encode to json: %w", err)
			}

			var m map[string]any
			if err := json.NewDecoder(&buf).Decode(&m); err != nil {
				return fmt.Errorf("decode from json: %w", err)
			}

			mv := mxj.Map(m)
			if err := mv.XmlWriter(w); err != nil {
				return fmt.Errorf("encode to xml: %w", err)
			}

			return nil
		}
	}

	if err := encode(resp); err != nil {
		log.Printf("[ERROR] Encode response: %v", err)
	} else {
		log.Printf("[INFO] Handled %s %s", r.Method, r.URL.Path)
	}
}

func writeError(statusCode int, err error, w http.ResponseWriter, r *http.Request) {
	resp := struct {
		XMLName xml.Name `json:"-" xml:"response"`
		Error   string   `json:"error" xml:"error"`
	}{Error: err.Error()}

	var encode func(any) error
	if r.Header.Get("Accept") == "application/xml" {
		w.Header().Add("Content-Type", "application/xml")

		encode = xml.NewEncoder(w).Encode
	} else {
		w.Header().Add("Content-Type", "application/json")

		encode = json.NewEncoder(w).Encode
	}

	w.WriteHeader(statusCode)

	if err := encode(resp); err != nil {
		log.Printf("[ERROR] Encode error response: %v", err)

		return
	}

	log.Printf("[INFO] Error %q for %s %s handled", err, r.Method, r.URL.Path)
}
