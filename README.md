# Sistema RU Arduino

Sistema simples utilizando leitor RFID e cartões de aproximação para realizar compras no restaurante universitário.

## Componentes

- **Arduino Uno Rev3**
- **ESP8266 ESP-01S**
- **Leitor RFID ACEBOTT RC522 RFID I2C**
- **Display LCD 16x2** (com interface I2C)
- **Keyestudio Digital Push Button Switch Module for Arduino**
- **Protoboard 400 Pontos**

## Livrarias

- [MFRC522_I2C](https://github.com/kkloesener/MFRC522_I2C)
- [LiquidCrystal I2C](https://github.com/johnrickman/LiquidCrystal_I2C)
- [SoftwareSerial](https://docs.arduino.cc/learn/built-in-libraries/software-serial/)
- [Wire](https://docs.arduino.cc/language-reference/en/functions/communication/wire/)

## Conexões

| Arduino Uno Pin | ESP8266 ESP-01S Pin |
|------------|---------------|
| D2 (GPI04) | TX            | 
| D3 (GPIO0) | RX            |
| CH-PD      | VCC           | 
| 3.3V       | VCC           | 
| GND        | GND           | 

| Arduino Uno Pin | LCD 16x2 I2C Pin |
|------------|---------|
| A4         | SDA     |
| A5         | SCL     |
| 5V         | VCC     |
| GND        | GND     |

| Arduino Uno Pin | RFID RC522 I2C Pin |
|------------|---------|
| A4         | SDA     |
| A5         | SCL     |
| 3.3V       | VCC     |
| GND        | GND     |

| Arduino Uno Pin | Button Pin |
|------------|---------|
| D5 (GPIO14) | S (Button)  |

## Configuração e execução

1. **Sistema:** Iniciar o sistema website/api.
2. **Instalar bibliotecas:** Abrir Arduino IDE -> Gerenciador de Bibliotecas e instalar todas as livrarias necessárias.
3. **Conexão Wi-fi:** Configurar o código principal do arduino para conexão Wifi, inserindo ssid e password.
4. **Carregar e iniciar:** Compilar o código e carregar o arquivo para o Arduino Uno.

**Aviso:** Certifique-se de que ambos (Arduino Uno e Sistema/API) estevam na mesma rede local e que o firewall não bloqueei as requisições.

### Fotos dos testes
![1](https://github.com/cauagrc/FazSistemaRU/blob/main/photos/1.png)
![2](https://github.com/cauagrc/FazSistemaRU/blob/main/photos/2.png)
![3](https://github.com/cauagrc/FazSistemaRU/blob/main/photos/3.png)
![4](https://github.com/cauagrc/FazSistemaRU/blob/main/photos/4.png)
![5](https://github.com/cauagrc/FazSistemaRU/blob/main/photos/5.png)
![6](https://github.com/cauagrc/FazSistemaRU/blob/main/photos/6.png)
[![Vídeo](https://imgur.com/S1Vog1C.png)](https://github.com/cauagrc/FazSistemaRU/blob/main/photos/Teste%20video.mp4?raw=true)