Benchmark
---------

Testing environment

* 8 vCPU
* 32 GB RAM
* Ubuntu 18.04
* Swoole version 4.2.12

Hetzner CX51 Virtual Machine from Cloud.

For look the pre-installed software see file `deps.sh`.

Command to run app:

```bash
php index.php
```

Command to run http tests:
```bash
wrk -t8 -c2000 -d15s http://127.0.0.1:8080/
```

Results:

```bash
root@benchmark-machine:~# wrk -t8 -c2000 -d15s http://127.0.0.1:8080/
Running 15s test @ http://127.0.0.1:8080/
  8 threads and 2000 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency     5.10ms    3.34ms  45.32ms   75.31%
    Req/Sec    23.05k     7.49k   60.42k    78.33%
  2756350 requests in 15.10s, 0.92GB read
  Socket errors: connect 987, read 0, write 0, timeout 0
Requests/sec: 182540.99
Transfer/sec:     62.67MB
```
