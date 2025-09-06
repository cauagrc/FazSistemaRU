#include <Wire.h>
#include "MFRC522_I2C.h"
#include <LiquidCrystal_I2C.h>
#include <SoftwareSerial.h>

#define BOTAO 5  //Botão conectado ao pino digital 5 do Arduino

SoftwareSerial esp(2, 3);//Portas do ESP8266 ESP-01S GPIO 1 -> pino digital 2 | GPIO 3 -> pino digital 3 

String ip = "0.0.0.0"; //Endereço do servidor onde está a api

LiquidCrystal_I2C lcd(0x27, 16, 2);//Endereço placa LCD I2C, colunas, linhas
MFRC522_I2C mfrc522(0x28, -1);//Endereço do leitor RFID

String rfid_str;
unsigned long tempoInicioMenu2 = 0;
int menuAtual = 1;
bool botaoPressionadoAnterior = false;

String resposta = "";
int isResponse = 0;

void sendCommand(String cmd, int waitTime = 2000) {
    //Mostra no console o comando recebido
    esp.println(cmd);

    delay(waitTime);

    //Verifica se o ESP está disponível e foi encontrando
    while (esp.available()) {
        //Envia o comando para o esp via console
        Serial.write(esp.read());
    }
}

void sendRegister(String rfid) {
    //Caminho para fazer requisição na api
    String url = "http://" + ip + "/sistemaru/api/users/" + rfid;  

    //Inicia a conexão com o servidor (Porta 80) 
    sendCommand("AT+CIPSTART=\"TCP\",\"" + ip + "\",80");
    delay(2000);

    //Montando a requisição
    String postRequest = "POST /sistemaru/api/users/" + rfid + " HTTP/1.1\r\n";
    postRequest += "Host: " + ip + "\r\n";
    postRequest += "Connection: close\r\n\r\n";
    postRequest += "\r\n";

    //Calculando tamanho da requisição que será enviada
    int length = postRequest.length();

    //Mostra no console o comando que será enviado para o ESP
    Serial.print("[ENVIO] AT+CIPSEND=");
    Serial.println(length);
    
    //Envia o comando AT+CIPSEND para informar ao ESP o tamanho da requisição
    esp.println("AT+CIPSEND=" + String(length));
    delay(2000);

    //Verifica se o ESP está ativo
    if (esp.available()) {
        //Verifica a resposta enviada do servidor para o ESP
        Serial.write(esp.read());

        //Cria variável para armazenar a resposta do servidor
        resposta = "";
        //Variável para armazenar apenas json da resposta
        isResponse = 0;

        Serial.println("[POST] Enviando requisição HTTP POST...");
        esp.print(postRequest);

        // Aguarda e lê a resposta do servidor
        unsigned long startTime = millis();
    
        //Armazenar a resposta,  delay de 3s para dar tempo de capturar resposta
        while (millis() - startTime < 3000) {  /
            //Verificar se o ESP ainda está online
            while (esp.available()) {
                char c = esp.read();
                Serial.print(c);

                //Verificar inicio e modificar para 1
                if (c == '{') 
                isResponse = 1;
                else {
                    //Verificar quando chega no final
                    if (c == '}')
                    isResponse = 0;
                }

                //Enquanto estiver dentro do json armazenar resposta
                if (isResponse == 1)    
                resposta += c;
            }
        }

        //Fechar conexão com servidor
        sendCommand("AT+CIPCLOSE", 1000);

        // Verifica se a resposta contém "status":"success"
        if (resposta.indexOf("\"status\":\"ok\"") != -1) {
            lcd.clear();
            lcd.setCursor(0, 0);
            lcd.print("Cadastro OK!");
            lcd.setCursor(0, 1);
            lcd.print("Termine no servidor");
        } else {
            lcd.clear();
            lcd.setCursor(0, 0);
            lcd.print("Erro no cadastro");
            lcd.setCursor(0, 1);
            lcd.print("Leia o cartao de novo");

            // Imprime erro para depuração
            Serial.println("Erro no cadastro: " + resposta);  
        }
    } else {
        // Se não houver resposta, exibe um erro
        lcd.clear();
        lcd.setCursor(0, 0);
        lcd.print("Erro ao conectar");
        lcd.setCursor(0, 1);
        lcd.print("Verifique servidor");
        Serial.println("Erro: Sem resposta do servidor");
    }
}

void sendBuy(String rfid) {
    //Caminho para fazer requisição na api
    String url = "http://" + ip + "/sistemaru/api/users/" + rfid + "/compra";

    //Inicia a conexão com o servidor (Porta 80) 
    sendCommand("AT+CIPSTART=\"TCP\",\"" + ip + "\",80");
    delay(2000);

    //Montando a requisição
    String postRequest = "POST /sistemaru/api/users/" + rfid + "/compra" + " HTTP/1.1\r\n";
    postRequest += "Host: " + ip + "\r\n";
    postRequest += "Connection: close\r\n\r\n";  
    postRequest += "\r\n";                       

    //Calculando tamanho da requisição que será enviada
    int length = postRequest.length();

    //Mostra no console o comando que será enviado para o ESP
    Serial.print("[ENVIO] AT+CIPSEND=");
    Serial.println(length);

    //Envia o comando AT+CIPSEND para informar ao ESP o tamanho da requisição
    esp.println("AT+CIPSEND=" + String(length));
    delay(2000);

    //Verifica se o ESP está ativo
    if (esp.available()) {
        //Verifica a resposta enviada do servidor para o ESP
        Serial.write(esp.read());

        //Variável para identificar a refeição
        String refeicao = "";
        //Cria variável para armazenar a resposta do servidor
        resposta = "";
        //Variável para armazenar apenas json da resposta
        i = 0;

        Serial.println("[POST] Enviando requisição HTTP POST...");
        esp.print(postRequest);

        // Aguarda e lê a resposta do servidor
        unsigned long startTime = millis();

        //Armazenar a resposta,  delay de 3s para dar tempo de capturar resposta
        while (millis() - startTime < 3000) { 
            //Verificar se o ESP ainda está online
            while (esp.available()) {
                char c = esp.read();
                Serial.print(c);

                //Verificar inicio e modificar para 1
                if (c == '{')
                isResponse = 1;
                else {
                    if (c == '}') 
                    //Verificar quando chega no final
                    isResponse = 0;
                }

                //Enquanto estiver dentro do json armazenar resposta
                if (isResponse == 1) 
                resposta += c;
            }
        }

        if (resposta.indexOf("\"refeicao\":1") != -1) 
        refeicao = "Cafe da manha";
        else {
            if (resposta.indexOf("\"refeicao\":2") != -1) 
            refeicao = "Almoco";
            else {
                if (resposta.indexOf("\"refeicao\":3") != -1) 
                refeicao = "Janta";
            }   
        }
        
        // Verifica se a resposta contém "status":"success"posta no LCD
        if (resposta.indexOf("\"status\":\"ok\"") != -1) {
            lcd.clear();
            lcd.setCursor(0, 0);
            lcd.print("Compra:");
            lcd.setCursor(0, 1);
            lcd.print(refeicao);
        } else if (resposta.indexOf("\"error\":\"Saldo insuficiente.\"") != -1) {
            lcd.clear();
            lcd.setCursor(0, 0);
            lcd.print("Saldo insuficien");
            lcd.setCursor(0, 1);
            lcd.print("te");
        } else if (resposta.indexOf("\"error\":\"Usuario nao encontrado\"") != -1) {
            lcd.clear();
            lcd.setCursor(0, 0);
            lcd.print("Cartao nao cadas");
            lcd.setCursor(0, 1);
            lcd.print("trado no sistema");
        } else {
            lcd.clear();
            lcd.setCursor(0, 0);
            lcd.print("Fora do horario");
            lcd.setCursor(0, 1);
            lcd.print("de refeicao.");
        }
    } else {
        // Se não houver resposta, exibe um erro
        lcd.clear();
        lcd.setCursor(0, 0);
        lcd.print("Erro ao conectar");
        lcd.setCursor(0, 1);
        lcd.print("Verifique servidor");
        Serial.println("Erro: Sem resposta do servidor");
    }
}

void setup() {
    //Inicialização da porta serial
    Serial.begin(9600);
    Wire.begin();
    esp.begin(9600);

    //Iniciando o leitor rfid
    mfrc522.PCD_Init();

    //Iniciando a tela lcd 16x2
    lcd.init();
    lcd.backlight();

    //Iniciando o botão e definindo o pino digital
    pinMode(BOTAO, INPUT_PULLUP);

    //Enviando comandos para conexão do ESP no Wifi
    //SSID = Nome do Wifi
    //password = Senha do Wifi
    sendCommand("AT");
    sendCommand("AT+RST", 3000);
    sendCommand("AT+CWMODE=1");
    sendCommand("AT+CWJAP=\"SSID\",\"password\"", 10000);
    sendCommand("AT+CIFSR");

    //Limpando a tela e escrevendo na LCD
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("Aproxime o carta");
    lcd.setCursor(0, 1);
    lcd.print("o...");
}

void loop() {
    bool botaoPressionado = digitalRead(BOTAO) == LOW;

    // Alternar entre menus ao pressionar botão
    if (botaoPressionado && !botaoPressionadoAnterior) {
        if (menuAtual == 1) {
            menuAtual = 2;
            tempoInicioMenu2 = millis();
            lcd.clear();
            lcd.setCursor(0, 0);
            lcd.print("Menu: Cadastro");
            lcd.setCursor(0, 1);
            lcd.print("Aproxime o cartao");
        } else {
            menuAtual = 1;
            lcd.clear();
            lcd.setCursor(0, 0);
            lcd.print("Aproxime o carta");
            lcd.setCursor(0, 1);
            lcd.print("o...");
        }
    }

    botaoPressionadoAnterior = botaoPressionado;

    //Se permanece no menu 2 durante dois minutos ele volta para o menu 1
    if (menuAtual == 2 && millis() - tempoInicioMenu2 > 60000) {
        menuAtual = 1;
        lcd.clear();
        lcd.setCursor(0, 0);
        lcd.print("Aproxime o carta");
        lcd.setCursor(0, 1);
        lcd.print("o...");
    }

    // Leitura do cartão
    if (!mfrc522.PICC_IsNewCardPresent() || !mfrc522.PICC_ReadCardSerial()) {
        delay(50);
        return;
    }

    //Armazenar o código que o leitor rfid recebeu
    rfid_str = "";
    for (byte i = 0; i < mfrc522.uid.size; i++) {
        rfid_str += (mfrc522.uid.uidByte[i] < 0x10 ? "0" : "");
        rfid_str += String(mfrc522.uid.uidByte[i], HEX);
    }

    Serial.println("Cartao lido: " + rfid_str);
    lcd.clear();

    //Verificar em qual menu está e executar a função
    if (menuAtual == 1) 
        sendBuy(rfid_str);
    else {
        sendRegister(rfid_str);
        // Volta para o menu de compra
        menuAtual = 1;  
    }

    delay(2000);
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("Aproxime o carta");
    lcd.setCursor(0, 1);
    lcd.print("o...");
}