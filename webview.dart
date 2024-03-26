import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  // Enable network connectivity checks (comment out to disable)
  await Connectivity().checkConnectivity();

  runApp(MyApp());
}

class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    // Customize colors for a pink theme
    return MaterialApp(
      theme: ThemeData(
        primarySwatch: Colors.pink,
        appBarTheme: const AppBarTheme(
          color: Colors.pink,
        ),
      ),
      home: WebViewExample(),
    );
  }
}

class WebViewExample extends StatefulWidget {
  @override
  _WebViewExampleState createState() => _WebViewExampleState();
}

class _WebViewExampleState extends State<WebViewExample> {
  WebViewController? _webViewController;
  String _title = "";
  bool _canGoBack = false;
  bool _canGoForward = false;
  bool _isLoading = true;

  // Connectivity handling variables
  bool _hasInternet = true;
  String _connectivityMessage = "";

  // Error handling and retrying
  bool _retryLoading = false;
  String _reloadUrl = "";

  @override
  void initState() {
    super.initState();
    // Check network connectivity on app launch
    Connectivity().onConnectivityChanged.listen((ConnectivityResult result) {
      if (result == ConnectivityResult.none) {
        setState(() {
          _hasInternet = false;
          _connectivityMessage = "No internet connection detected.";
        });
      } else {
        setState(() {
          _hasInternet = true;
          _connectivityMessage = "";
          // Reload the page if previously loading failed due to no internet
          if (_retryLoading) {
            _webViewController?.loadUrl(_reloadUrl);
            _retryLoading = false;
          }
        });
      }
    });
  }

  @override
  void dispose() {
    _webViewController?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(_title),
        // Add forward/backward navigation buttons (dynamically enabled/disabled)
        actions: <Widget>[
          IconButton(
            icon: const Icon(Icons.arrow_back),
            onPressed: _canGoBack ? () => _webViewController?.goBack() : null,
          ),
          IconButton(
            icon: const Icon(Icons.arrow_forward),
            onPressed:
                _canGoForward ? () => _webViewController?.goForward() : null,
          ),
        ],
      ),
      body: _hasInternet
          ? Stack(
              children: [
                WebView(
                  initialUrl: "https://google.com",
                  onWebViewCreated: (WebViewController webViewController) {
                    _webViewController = webViewController;
                    _webViewController?.navigationDelegate =
                        (NavigationRequest request) {
                      _urlNavigationDelegate(request);
                      return NavigationDecision.navigate;
                    };
                    _webViewController?.onPageStarted = (url) {
                      setState(() {
                        _isLoading = true;
                      });
                    };
                    _webViewController?.onPageFinished = (url) {
                      setState(() {
                        _isLoading = false;
                        _title = _webViewController?.title ?? "";
                      });
                    };
                  },
                  onPageStarted: (url) {
                    _updateNavigation();
                  },
                  onPageFinished: (url) {
                    _updateNavigation();
                  },
                  onWebResourceError: (WebResourceError error) {
                    // Custom error handling (display user-friendly message)
                    if (error.description.contains("net::")) {
                      setState(() {
                        _hasInternet = false;
                        _connectivityMessage =
                            "Network error occurred. Please retry.";
                        _retryLoading = true;
                        _reloadUrl = url;
                      });
                    }
                  },
                ),
                _isLoading
                    ? Center(child: CircularProgressIndicator())
                    : SizedBox(),
              ],
            )
          // Display internet connectivity error popup
          : Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(
                    Icons.signal_cellular_connected_no_internet_4_bar,
                    color: Colors.pink,
                    size: 64.0,
                  ),
                  Text(
                    _connectivityMessage,
                    style: TextStyle(
                      fontSize: 18.0,
                      color: Colors.pink,
                    ),
                  ),
                  // Add a retry button for user action
                  TextButton(
                    onPressed: () =>
                        Connectivity().checkConnectivity().then((result) {
                      if (result == ConnectivityResult.none) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text('No internet connection available.'),
                          ),
                        );
                      } else {
                        setState(() {
                          _hasInternet = true;
                          _connectivityMessage = "";
                        });
                      }
                    }),
                    child: Text(
                      "Retry",
                      style: TextStyle(
                        color: Colors.pink,
                      ),
                    ),
                  ),
                ],
              ),
            ),
    );
  }

  // Update navigation state (canGoBack, canGoForward)
  void _updateNavigation() {
    _webViewController?.canGoBack().then((canGoBack) {
      _canGoBack = canGoBack;
    });
    _webViewController?.canGoForward().then((canGoForward) {
      _canGoForward = canGoForward;
    });
    setState(() {});
  }

  // URL navigation delegate for handling custom logic
  void _urlNavigationDelegate(NavigationRequest request) {
    // Example: Filter links to open in external browser
    if (request.url.startsWith("https://www.suuqone.com")) {
      launch(request.url);
    } else {
      _webViewController?.loadUrl(request.url);
    }
  }
}
