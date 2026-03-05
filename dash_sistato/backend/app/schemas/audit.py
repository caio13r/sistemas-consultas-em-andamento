from pydantic import BaseModel
from typing import Optional, List
from datetime import datetime
from enum import Enum

class RequestStatus(str, Enum):
    PENDING = "pending"
    SENT = "sent"
    IN_ANALYSIS = "in_analysis"
    APPROVED = "approved"
    REJECTED = "rejected"

class ThemeBase(BaseModel):
    name: str
    description: str
    category: str
    status: str = "active"

class ThemeCreate(ThemeBase):
    pass

class Theme(ThemeBase):
    id: int
    created_at: datetime
    updated_at: Optional[datetime] = None

    class Config:
        from_attributes = True

class AuditBase(BaseModel):
    theme_id: int
    title: str
    description: str
    deadline: datetime
    status: str = "draft"

class AuditCreate(AuditBase):
    pass

class Audit(AuditBase):
    id: int
    created_by: int
    created_at: datetime
    updated_at: Optional[datetime] = None
    theme: Theme

    class Config:
        from_attributes = True

class RequestBase(BaseModel):
    audit_id: int
    cro_id: int
    status: RequestStatus = RequestStatus.PENDING
    message: Optional[str] = None

class RequestCreate(RequestBase):
    pass

class Request(RequestBase):
    id: int
    created_at: datetime
    updated_at: Optional[datetime] = None
    audit: Audit

    class Config:
        from_attributes = True

class DocumentBase(BaseModel):
    audit_id: int
    request_id: Optional[int] = None
    filename: str
    file_path: str
    file_type: str
    file_size: int
    is_validated: bool = False

class DocumentCreate(DocumentBase):
    pass

class Document(DocumentBase):
    id: int
    validation_date: Optional[datetime] = None
    created_at: datetime
    updated_at: Optional[datetime] = None
    audit: Audit
    request: Optional[Request] = None

    class Config:
        from_attributes = True

class CommunicationBase(BaseModel):
    audit_id: int
    receiver_id: int
    message: str

class CommunicationCreate(CommunicationBase):
    pass

class Communication(CommunicationBase):
    id: int
    sender_id: int
    is_read: bool
    created_at: datetime
    audit: Audit
    sender: dict
    receiver: dict

    class Config:
        from_attributes = True 