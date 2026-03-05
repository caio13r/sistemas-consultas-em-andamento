from sqlalchemy import Column, Integer, String, Boolean, ForeignKey
from sqlalchemy.orm import relationship
from ..database import Base

class MenuItem(Base):
    __tablename__ = "tbl_child_labels"

    id_label = Column(Integer, primary_key=True, index=True)
    nome = Column(String)
    referencial = Column(String)
    grupo = Column(String)
    descricao = Column(String)
    disable = Column(Boolean, default=False)
    fk_label = Column(Integer, ForeignKey("tbl_child_labels.id_label"), nullable=True)

    # Self-referential relationship for parent-child menu items
    children = relationship("MenuItem",
                          backref="parent",
                          remote_side=[id_label],
                          cascade="all",
                          single_parent=True) 