#!/usr/bin/env python3
"""
Script para testar o sistema de permissões
"""

import requests
import json
import sys
from typing import Dict, List

# Configuração
BASE_URL = "http://localhost:8000"
ADMIN_CREDENTIALS = {
    "username": "admin",
    "password": "admin123"
}

class PermissionTester:
    def __init__(self):
        self.session = requests.Session()
        self.token = None
        self.user_data = None

    def login(self, credentials: Dict[str, str]) -> bool:
        """Faz login e obtém token"""
        try:
            response = self.session.post(
                f"{BASE_URL}/token",
                data=credentials
            )
            
            if response.status_code == 200:
                data = response.json()
                self.token = data["access_token"]
                self.user_data = data["user"]
                self.session.headers.update({
                    "Authorization": f"Bearer {self.token}"
                })
                print(f"✅ Login realizado com sucesso!")
                print(f"   Usuário: {self.user_data['username']}")
                print(f"   Permissões: {len(data['permissions'])}")
                print(f"   Roles: {data['roles']}")
                return True
            else:
                print(f"❌ Erro no login: {response.status_code}")
                return False
        except Exception as e:
            print(f"❌ Erro de conexão: {e}")
            return False

    def test_menu_endpoint(self) -> bool:
        """Testa o endpoint de menu dinâmico"""
        try:
            response = self.session.get(f"{BASE_URL}/api/menu")
            
            if response.status_code == 200:
                data = response.json()
                print(f"✅ Menu carregado com sucesso!")
                print(f"   Menus disponíveis: {len(data['menu'])}")
                for menu in data['menu']:
                    print(f"   - {menu['name']} ({len(menu['submenus'])} submenus)")
                return True
            else:
                print(f"❌ Erro ao carregar menu: {response.status_code}")
                return False
        except Exception as e:
            print(f"❌ Erro de conexão: {e}")
            return False

    def test_permissions_endpoint(self) -> bool:
        """Testa o endpoint de permissões"""
        try:
            response = self.session.get(f"{BASE_URL}/api/permissions")
            
            if response.status_code == 200:
                permissions = response.json()
                print(f"✅ Permissões carregadas: {len(permissions)}")
                return True
            else:
                print(f"❌ Erro ao carregar permissões: {response.status_code}")
                return False
        except Exception as e:
            print(f"❌ Erro de conexão: {e}")
            return False

    def test_roles_endpoint(self) -> bool:
        """Testa o endpoint de roles"""
        try:
            response = self.session.get(f"{BASE_URL}/api/roles")
            
            if response.status_code == 200:
                roles = response.json()
                print(f"✅ Roles carregados: {len(roles)}")
                for role in roles:
                    print(f"   - {role['name']}: {role['description']}")
                return True
            else:
                print(f"❌ Erro ao carregar roles: {response.status_code}")
                return False
        except Exception as e:
            print(f"❌ Erro de conexão: {e}")
            return False

    def test_menus_endpoint(self) -> bool:
        """Testa o endpoint de menus"""
        try:
            response = self.session.get(f"{BASE_URL}/api/menus")
            
            if response.status_code == 200:
                menus = response.json()
                print(f"✅ Menus carregados: {len(menus)}")
                for menu in menus:
                    print(f"   - {menu['name']}: {menu['url']}")
                return True
            else:
                print(f"❌ Erro ao carregar menus: {response.status_code}")
                return False
        except Exception as e:
            print(f"❌ Erro de conexão: {e}")
            return False

    def test_permission_check(self, permission_name: str) -> bool:
        """Testa verificação de permissão específica"""
        try:
            response = self.session.get(f"{BASE_URL}/api/menu/check-permission/{permission_name}")
            
            if response.status_code == 200:
                data = response.json()
                has_permission = data["has_permission"]
                print(f"✅ Verificação de permissão '{permission_name}': {'✅ Tem permissão' if has_permission else '❌ Sem permissão'}")
                return has_permission
            else:
                print(f"❌ Erro ao verificar permissão: {response.status_code}")
                return False
        except Exception as e:
            print(f"❌ Erro de conexão: {e}")
            return False

    def test_users_endpoint(self) -> bool:
        """Testa o endpoint de usuários"""
        try:
            response = self.session.get(f"{BASE_URL}/users")
            
            if response.status_code == 200:
                users = response.json()
                print(f"✅ Usuários carregados: {len(users)}")
                return True
            else:
                print(f"❌ Erro ao carregar usuários: {response.status_code}")
                return False
        except Exception as e:
            print(f"❌ Erro de conexão: {e}")
            return False

    def run_all_tests(self):
        """Executa todos os testes"""
        print("🚀 Iniciando testes do sistema de permissões...")
        print("=" * 50)

        # Teste de login
        if not self.login(ADMIN_CREDENTIALS):
            print("❌ Falha no login. Abortando testes.")
            return False

        print("\n📋 Executando testes de endpoints...")
        
        tests = [
            ("Menu Dinâmico", self.test_menu_endpoint),
            ("Permissões", self.test_permissions_endpoint),
            ("Roles", self.test_roles_endpoint),
            ("Menus", self.test_menus_endpoint),
            ("Usuários", self.test_users_endpoint),
        ]

        passed = 0
        total = len(tests)

        for test_name, test_func in tests:
            print(f"\n🔍 Testando: {test_name}")
            if test_func():
                passed += 1
            else:
                print(f"   ❌ Teste falhou: {test_name}")

        print(f"\n📊 Resultados dos testes:")
        print(f"   ✅ Passou: {passed}/{total}")
        print(f"   ❌ Falhou: {total - passed}/{total}")

        # Testes de permissões específicas
        print(f"\n🔐 Testando permissões específicas...")
        test_permissions = [
            "view_users",
            "create_users",
            "manage_roles",
            "manage_permissions",
            "view_consulta_integrada"
        ]

        for perm in test_permissions:
            self.test_permission_check(perm)

        return passed == total

def main():
    """Função principal"""
    tester = PermissionTester()
    
    try:
        success = tester.run_all_tests()
        
        if success:
            print("\n🎉 Todos os testes passaram! Sistema funcionando corretamente.")
            sys.exit(0)
        else:
            print("\n⚠️  Alguns testes falharam. Verifique o sistema.")
            sys.exit(1)
            
    except KeyboardInterrupt:
        print("\n⏹️  Testes interrompidos pelo usuário.")
        sys.exit(1)
    except Exception as e:
        print(f"\n💥 Erro inesperado: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main() 