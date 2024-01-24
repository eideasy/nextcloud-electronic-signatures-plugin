class RequestError extends Error {
  constructor(message, cause) {
    super(message);
    this.cause = cause;
    this.name = 'RequestError';
    this.debugInfo = {};

    if (cause.config) {
      this.debugInfo.request = {
        url: cause.config.url,
        method: cause.config.method,
        data: JSON.parse(cause.config.data),
      }
    }

    if (cause.response) {
      this.debugInfo.response = {
        status: cause.response.status,
        statusText: cause.response.statusText,
        data: JSON.parse(cause.response.data),
      }
    }

  }


  get debugInfoPrettyString() {
    return JSON.stringify(this.debugInfo, null, 4);
  }
}

export default RequestError;
